<?php

namespace App\Actions;

use App\Enums\GenerationStatus;
use App\Models\CostBaseline;
use App\Models\Generation;
use App\Money\CustomerPrice;
use Illuminate\Support\Facades\DB;

/**
 * Learns the cost estimate from real prices: adds a succeeded generation's price to the baseline
 * of its size × quality.
 *
 * Call it once per generation, in the same transaction as its move to `succeeded` (#13). That
 * move is forward-only and happens once, so a generation is never counted twice.
 */
class RecordCostBaseline
{
    /**
     * Input: a generation. Only a succeeded one with an exact price (`price_nusd`) is recorded;
     * the price is rounded up to whole cents with {@see CustomerPrice::roundUpToCents()}.
     * Output: true when the baseline was updated, false when the generation was skipped.
     */
    public function handle(Generation $generation): bool
    {
        if ($generation->status !== GenerationStatus::Succeeded || $generation->price_nusd === null) {
            return false;
        }

        $price = CustomerPrice::roundUpToCents($generation->price_nusd);

        DB::transaction(fn () => $this->lockedBaselineFor($generation)->addSample($price)->save());

        return true;
    }

    /**
     * Input: a generation. Output: its baseline row, locked until the transaction ends so
     * concurrent recordings cannot overwrite each other, or a new seed-range row if missing.
     */
    private function lockedBaselineFor(Generation $generation): CostBaseline
    {
        return CostBaseline::query()
            ->where('size', $generation->size)
            ->where('quality', $generation->quality)
            ->lockForUpdate()
            ->first()
            ?? new CostBaseline(['size' => $generation->size, 'quality' => $generation->quality, ...CostBaseline::seedRange()]);
    }
}

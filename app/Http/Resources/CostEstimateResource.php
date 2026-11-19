<?php

namespace App\Http\Resources;

use App\Models\CostBaseline;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A cost estimate: the customer price range for one size × quality.
 *
 * @mixin CostBaseline
 */
class CostEstimateResource extends JsonResource
{
    /**
     * Output: {size, quality, min_cents, max_cents, sample_count}; amounts in whole US cents.
     * sample_count is 0 while the range is still the seed range.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'size' => $this->size->value,
            'quality' => $this->quality->value,
            'min_cents' => $this->min_cents->cents,
            'max_cents' => $this->max_cents->cents,
            'sample_count' => $this->sample_count,
        ];
    }
}

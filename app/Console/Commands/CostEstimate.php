<?php

namespace App\Console\Commands;

use App\Actions\EstimateCost;
use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('cost:estimate {size : 1024x1024, 1536x1024 or 1024x1536} {quality : low, medium or high}')]
#[Description('Show the estimated customer price range for a size and quality')]
class CostEstimate extends Command
{
    /**
     * Input: size and quality. Output: e.g. "1024x1024 · high: $0.12 – $0.47 (4 samples)";
     * exit 1 for an unknown size or quality.
     */
    public function handle(EstimateCost $estimateCost): int
    {
        $size = ImageSize::tryFrom((string) $this->argument('size'));
        $quality = ImageQuality::tryFrom((string) $this->argument('quality'));

        if ($size === null || $quality === null) {
            $this->components->error('Unknown size or quality. Run with --help to see the accepted values.');

            return self::FAILURE;
        }

        $baseline = $estimateCost->handle($size, $quality);
        $this->line(sprintf(
            '%s · %s: %s – %s (%d samples)',
            $size->value,
            $quality->value,
            $baseline->min_cents->format(),
            $baseline->max_cents->format(),
            $baseline->sample_count,
        ));

        return self::SUCCESS;
    }
}

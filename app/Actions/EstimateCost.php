<?php

namespace App\Actions;

use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Models\CostBaseline;

/**
 * The cost estimate for a generation, before it is submitted. Shared by the web form, the API
 * and the console.
 */
class EstimateCost
{
    /**
     * Input: the chosen size and quality.
     * Output: their baseline, whose min_cents – max_cents is the estimated customer price range.
     * With no recorded generations yet it is the wide seed range, and it is never saved here.
     */
    public function handle(ImageSize $size, ImageQuality $quality): CostBaseline
    {
        return CostBaseline::forOptions($size, $quality);
    }
}

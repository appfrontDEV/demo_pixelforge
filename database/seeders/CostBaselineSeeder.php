<?php

namespace Database\Seeders;

use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Models\CostBaseline;
use Illuminate\Database\Seeder;

class CostBaselineSeeder extends Seeder
{
    /**
     * Creates the 9 size × quality baselines at the seed range. Idempotent: existing rows,
     * including ones that already have samples, are left as they are.
     */
    public function run(): void
    {
        foreach (ImageSize::cases() as $size) {
            foreach (ImageQuality::cases() as $quality) {
                CostBaseline::forOptions($size, $quality)->save();
            }
        }
    }
}

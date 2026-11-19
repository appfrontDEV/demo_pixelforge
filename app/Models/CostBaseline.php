<?php

namespace App\Models;

use App\Casts\AsCents;
use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Money\Cents;
use Database\Factories\CostBaselineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The empirical cost estimate for one size × quality: the lowest and highest customer price
 * (rounded up to whole cents) of the generations recorded so far.
 *
 * Until the first sample arrives the range is the wide seed range, SEED_MIN_CENTS – SEED_MAX_CENTS.
 *
 * @property int $id
 * @property ImageSize $size
 * @property ImageQuality $quality
 * @property Cents $min_cents
 * @property Cents $max_cents
 * @property int $sample_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['size', 'quality', 'min_cents', 'max_cents', 'sample_count'])]
class CostBaseline extends Model
{
    /** @use HasFactory<CostBaselineFactory> */
    use HasFactory;

    public const int SEED_MIN_CENTS = 0;

    public const int SEED_MAX_CENTS = 100_000;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => ImageSize::class,
            'quality' => ImageQuality::class,
            'min_cents' => AsCents::class,
            'max_cents' => AsCents::class,
            'sample_count' => 'integer',
        ];
    }

    /**
     * Input: a size and a quality.
     * Output: their stored baseline, or a new unsaved one at the seed range when none exists.
     */
    public static function forOptions(ImageSize $size, ImageQuality $quality): self
    {
        return static::query()->firstOrNew(
            ['size' => $size, 'quality' => $quality],
            self::seedRange(),
        );
    }

    /**
     * Output: the attributes of a baseline with no samples yet.
     *
     * @return array{min_cents: int, max_cents: int, sample_count: int}
     */
    public static function seedRange(): array
    {
        return ['min_cents' => self::SEED_MIN_CENTS, 'max_cents' => self::SEED_MAX_CENTS, 'sample_count' => 0];
    }

    /**
     * Input: one generation's customer price in whole cents. Output: this baseline, not saved.
     * The first sample replaces the seed range with [price, price]; later ones only widen it.
     */
    public function addSample(Cents $price): self
    {
        $isFirst = $this->sample_count === 0;

        $this->min_cents = $isFirst || $price->isLessThan($this->min_cents) ? $price : $this->min_cents;
        $this->max_cents = $isFirst || $this->max_cents->isLessThan($price) ? $price : $this->max_cents;
        $this->sample_count++;

        return $this;
    }
}

<?php

namespace Tests\Unit\Casts;

use App\Casts\AsCents;
use App\Casts\AsNanoUsd;
use App\Money\Cents;
use App\Money\NanoUsd;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test strategy: casts map BIGINT columns to money value objects.
 * - get(): accepts int or digit strings (some drivers return BIGINT as string) and null.
 * - set(): accepts only the matching value object, an int, or null; floats and strings are rejected
 *   so a float can never reach a money column.
 */
class MoneyCastsTest extends TestCase
{
    public function test_nano_usd_cast_reads_integers_and_digit_strings(): void
    {
        $cast = new AsNanoUsd;

        $this->assertSame(373_875_000, $this->read($cast, 373_875_000)?->nanos);
        $this->assertSame(373_875_000, $this->read($cast, '373875000')?->nanos);
        $this->assertSame(-5, $this->read($cast, '-5')?->nanos);
        $this->assertNull($this->read($cast, null));
    }

    public function test_cents_cast_reads_integers_and_digit_strings(): void
    {
        $cast = new AsCents;

        $this->assertSame(47, $this->read($cast, 47)?->cents);
        $this->assertSame(47, $this->read($cast, '47')?->cents);
        $this->assertNull($this->read($cast, null));
    }

    public function test_casts_write_value_objects_integers_and_null(): void
    {
        $this->assertSame(373_875_000, $this->write(new AsNanoUsd, NanoUsd::fromNanos(373_875_000)));
        $this->assertSame(47, $this->write(new AsCents, Cents::fromCents(47)));
        $this->assertSame(12, $this->write(new AsCents, 12));
        $this->assertNull($this->write(new AsNanoUsd, null));
    }

    /**
     * @return array<string, array{CastsAttributes<mixed, mixed>, mixed}>
     */
    public static function rejectedWrites(): array
    {
        return [
            'float into nano column' => [new AsNanoUsd, 0.373875],
            'whole float into nano column' => [new AsNanoUsd, 1.0],
            'decimal string into nano column' => [new AsNanoUsd, '0.373875000'],
            'float into cents column' => [new AsCents, 0.47],
            'cents into nano column' => [new AsNanoUsd, Cents::fromCents(47)],
            'nanos into cents column' => [new AsCents, NanoUsd::fromNanos(1)],
        ];
    }

    /**
     * @param  CastsAttributes<mixed, mixed>  $cast
     */
    #[DataProvider('rejectedWrites')]
    public function test_casts_reject_floats_strings_and_wrong_units(CastsAttributes $cast, mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->write($cast, $value);
    }

    public function test_casts_reject_non_numeric_stored_values(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->read(new AsNanoUsd, '0.37');
    }

    public function test_nano_usd_serializes_as_nine_decimal_string(): void
    {
        $serialized = (new AsNanoUsd)->serialize($this->model(), 'cost', NanoUsd::fromNanos(373_875_000), []);

        $this->assertSame('0.373875000', $serialized);
    }

    /**
     * Run a cast's get() against an anonymous model.
     *
     * @param  CastsAttributes<mixed, mixed>  $cast
     */
    private function read(CastsAttributes $cast, mixed $stored): mixed
    {
        return $cast->get($this->model(), 'amount', $stored, []);
    }

    /**
     * Run a cast's set() against an anonymous model.
     *
     * @param  CastsAttributes<mixed, mixed>  $cast
     */
    private function write(CastsAttributes $cast, mixed $value): mixed
    {
        return $cast->set($this->model(), 'amount', $value, []);
    }

    private function model(): Model
    {
        return new class extends Model {};
    }
}

<?php

namespace Tests\Unit\Money;

use App\Money\NanoUsd;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test strategy: NanoUsd is the exact provider-cost unit (1e-9 USD).
 * - Parsing accepts only plain decimal strings with up to 9 decimals (the Pixelforge money format)
 *   and rejects anything a float could have produced (exponents, missing digits, too many decimals).
 * - Formatting always yields 9 decimals so values compare 1:1 with GET /v1/usage.
 * - Arithmetic stays in integers, including negative results used by report diffs.
 */
class NanoUsdTest extends TestCase
{
    /**
     * @return array<string, array{string, int}>
     */
    public static function validDecimalStrings(): array
    {
        return [
            'nine decimals' => ['0.373875000', 373_875_000],
            'smallest unit' => ['0.000000001', 1],
            'rate with two decimals' => ['12.50', 12_500_000_000],
            'integer' => ['5', 5_000_000_000],
            'zero' => ['0.000000000', 0],
            'negative' => ['-0.046125000', -46_125_000],
        ];
    }

    #[DataProvider('validDecimalStrings')]
    public function test_it_parses_decimal_strings_exactly(string $decimal, int $expectedNanos): void
    {
        $this->assertSame($expectedNanos, NanoUsd::fromDecimalString($decimal)->nanos);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidDecimalStrings(): array
    {
        return [
            'exponent notation' => ['1e-9'],
            'missing leading digit' => ['.5'],
            'missing decimals' => ['1.'],
            'explicit plus sign' => ['+1'],
            'ten decimals' => ['0.1234567891'],
            'surrounding whitespace' => [' 1.00 '],
            'thousands separator' => ['1,000.00'],
            'empty' => [''],
            'text' => ['abc'],
        ];
    }

    #[DataProvider('invalidDecimalStrings')]
    public function test_it_rejects_non_exact_decimal_strings(string $decimal): void
    {
        $this->expectException(InvalidArgumentException::class);

        NanoUsd::fromDecimalString($decimal);
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function formattedValues(): array
    {
        return [
            'worked example' => [373_875_000, '0.373875000'],
            'smallest unit' => [1, '0.000000001'],
            'one and a half dollars' => [1_500_000_000, '1.500000000'],
            'zero' => [0, '0.000000000'],
            'negative below one dollar' => [-46_125_000, '-0.046125000'],
            'negative above one dollar' => [-2_000_000_001, '-2.000000001'],
        ];
    }

    #[DataProvider('formattedValues')]
    public function test_it_formats_with_nine_decimals(int $nanos, string $expected): void
    {
        $this->assertSame($expected, NanoUsd::fromNanos($nanos)->toDecimalString());
    }

    public function test_it_round_trips_decimal_strings(): void
    {
        $this->assertSame('0.169080000', NanoUsd::fromDecimalString('0.169080000')->toDecimalString());
    }

    public function test_it_adds_and_subtracts_in_integers(): void
    {
        $cost = NanoUsd::fromDecimalString('0.373875000');

        $this->assertSame(747_750_000, $cost->add($cost)->nanos);
        $this->assertSame(-373_875_000, NanoUsd::zero()->subtract($cost)->nanos);
    }

    public function test_it_sums_large_amounts_without_losing_precision(): void
    {
        $total = NanoUsd::zero();

        for ($i = 0; $i < 1_000_000; $i++) {
            $total = $total->add(NanoUsd::fromNanos(373_875_001));
        }

        $this->assertSame('373875.001000000', $total->toDecimalString());
    }

    public function test_it_compares_amounts(): void
    {
        $small = NanoUsd::fromNanos(1);
        $large = NanoUsd::fromNanos(2);

        $this->assertTrue($small->equals(NanoUsd::fromNanos(1)));
        $this->assertTrue($small->isLessThan($large));
        $this->assertFalse($large->isLessThan($small));
        $this->assertTrue(NanoUsd::zero()->isZero());
        $this->assertTrue(NanoUsd::fromNanos(-1)->isNegative());
    }
}

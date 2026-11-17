<?php

namespace Tests\Unit\Money;

use App\Money\Cents;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test strategy: Cents is the customer-facing unit ($100 = 10000).
 * - Display formatting is pure integer math (no float helpers), including negatives.
 * - Arithmetic and comparisons stay in integers.
 */
class CentsTest extends TestCase
{
    /**
     * @return array<string, array{int, string}>
     */
    public static function displayValues(): array
    {
        return [
            'worked example price' => [47, '$0.47'],
            'one cent' => [1, '$0.01'],
            'zero' => [0, '$0.00'],
            'hundred dollars' => [10_000, '$100.00'],
            'thousands' => [123_456_789, '$1,234,567.89'],
            'negative' => [-47, '-$0.47'],
        ];
    }

    #[DataProvider('displayValues')]
    public function test_it_formats_for_display(int $cents, string $expected): void
    {
        $this->assertSame($expected, Cents::fromCents($cents)->format());
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function decimalValues(): array
    {
        return [
            'worked example price' => [47, '0.47'],
            'hundred dollars' => [10_000, '100.00'],
            'negative' => [-1_005, '-10.05'],
        ];
    }

    #[DataProvider('decimalValues')]
    public function test_it_formats_as_plain_decimal_string(int $cents, string $expected): void
    {
        $this->assertSame($expected, Cents::fromCents($cents)->toDecimalString());
    }

    public function test_it_adds_subtracts_and_compares(): void
    {
        $price = Cents::fromCents(47);

        $this->assertSame(94, $price->add($price)->cents);
        $this->assertSame(-47, Cents::zero()->subtract($price)->cents);
        $this->assertTrue($price->equals(Cents::fromCents(47)));
        $this->assertTrue(Cents::zero()->isLessThan($price));
        $this->assertTrue(Cents::zero()->isZero());
    }
}

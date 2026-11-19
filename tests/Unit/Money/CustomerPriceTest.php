<?php

namespace Tests\Unit\Money;

use App\Money\CustomerPrice;
use App\Money\NanoUsd;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test strategy: customer price = provider cost × 1.25, kept exact in nano-USD per generation.
 * Rounding UP to whole cents happens once, on a total: any total above zero is at least 1 cent,
 * a failed generation (cost 0) is free, and summing before rounding never charges more than
 * rounding each generation.
 */
class CustomerPriceTest extends TestCase
{
    /**
     * @return array<string, array{int, int}>
     */
    public static function providerCosts(): array
    {
        return [
            'documented maximum 0.373875 -> 46.734375c' => [373_875_000, 47],
            'documented succeeded example 0.30551 -> 38.19c' => [305_510_000, 39],
            'just above 3 cents (3.01c)' => [24_080_000, 4],
            'exactly 4 cents' => [32_000_000, 4],
            'a thousandth of a cent' => [8_000, 1],
            'smallest unit' => [1, 1],
            'zero (failed generation)' => [0, 0],
            'one hundred dollars of cost' => [100_000_000_000, 12_500],
        ];
    }

    #[DataProvider('providerCosts')]
    public function test_a_total_cost_marks_up_and_rounds_up_to_whole_cents(int $costNanos, int $expectedCents): void
    {
        $price = CustomerPrice::fromProviderCost(NanoUsd::fromNanos($costNanos));

        $this->assertSame($expectedCents, $price->cents);
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function exactPrices(): array
    {
        return [
            'documented maximum 0.373875 -> 0.46734375' => [373_875_000, 467_343_750],
            'documented succeeded example 0.30551 -> 0.3818875' => [305_510_000, 381_887_500],
            'one text token at $5/1M (5,000 nanos)' => [5_000, 6_250],
            'zero (failed generation)' => [0, 0],
            'a fraction of a nano rounds up' => [1, 2],
        ];
    }

    #[DataProvider('exactPrices')]
    public function test_it_keeps_the_exact_price_in_nanos(int $costNanos, int $expectedNanos): void
    {
        $price = CustomerPrice::exactFromProviderCost(NanoUsd::fromNanos($costNanos));

        $this->assertSame($expectedNanos, $price->nanos);
    }

    public function test_summing_exact_prices_then_rounding_once_avoids_per_row_round_ups(): void
    {
        // Three cheap generations at $0.0119 each (customer price).
        $prices = array_fill(0, 3, NanoUsd::fromNanos(11_900_000));

        $perRow = array_sum(array_map(fn (NanoUsd $p): int => CustomerPrice::roundUpToCents($p)->cents, $prices));
        $total = CustomerPrice::roundUpToCents(array_reduce($prices, fn (NanoUsd $sum, NanoUsd $p) => $sum->add($p), NanoUsd::zero()));

        $this->assertSame(6, $perRow);
        $this->assertSame(4, $total->cents);
    }

    public function test_rounding_to_cents_is_up_and_at_least_one_cent(): void
    {
        $this->assertSame(0, CustomerPrice::roundUpToCents(NanoUsd::zero())->cents);
        $this->assertSame(1, CustomerPrice::roundUpToCents(NanoUsd::fromNanos(1))->cents);
        $this->assertSame(1, CustomerPrice::roundUpToCents(NanoUsd::fromNanos(10_000_000))->cents);
        $this->assertSame(2, CustomerPrice::roundUpToCents(NanoUsd::fromNanos(10_000_001))->cents);
    }

    public function test_it_rejects_a_negative_price(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CustomerPrice::roundUpToCents(NanoUsd::fromNanos(-1));
    }

    public function test_it_rejects_a_negative_provider_cost(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CustomerPrice::fromProviderCost(NanoUsd::fromNanos(-1));
    }
}

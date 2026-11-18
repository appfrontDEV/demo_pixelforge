<?php

namespace Tests\Unit\Money;

use App\Money\CustomerPrice;
use App\Money\NanoUsd;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test strategy: customer price = provider cost × 1.25, always rounded UP to whole cents,
 * so any cost above zero is charged at least 1 cent and a failed generation (cost 0) is free.
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
    public function test_it_marks_up_and_rounds_up_to_whole_cents(int $costNanos, int $expectedCents): void
    {
        $price = CustomerPrice::fromProviderCost(NanoUsd::fromNanos($costNanos));

        $this->assertSame($expectedCents, $price->cents);
    }

    public function test_it_rejects_a_negative_provider_cost(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CustomerPrice::fromProviderCost(NanoUsd::fromNanos(-1));
    }
}

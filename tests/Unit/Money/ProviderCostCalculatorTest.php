<?php

namespace Tests\Unit\Money;

use App\Money\ProviderCostCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test strategy: the provider cost is Σ(tokens × rate per 1M) computed from a generation's own
 * `usage` and `rates`, exactly as Pixelforge bills it (never rounded).
 * Data structures mirror the API:
 * - usage: {input_text_tokens, input_image_tokens, output_tokens, reasoning_tokens, total_tokens} as ints
 * - rates: same keys (without total_tokens) as decimal strings in USD per 1M tokens
 * Fixtures are the two worked examples from the Pixelforge docs.
 */
class ProviderCostCalculatorTest extends TestCase
{
    /**
     * Rates published by GET /v1/pricing.
     *
     * @return array<string, string>
     */
    private static function documentedRates(): array
    {
        return [
            'input_text_tokens' => '5.00',
            'input_image_tokens' => '10.00',
            'output_tokens' => '40.00',
            'reasoning_tokens' => '12.50',
        ];
    }

    public function test_it_matches_the_documented_maximum_cost(): void
    {
        $usage = [
            'input_text_tokens' => 115,
            'input_image_tokens' => 1_530,
            'output_tokens' => 6_450,
            'reasoning_tokens' => 8_000,
        ];

        $cost = (new ProviderCostCalculator)->calculate($usage, self::documentedRates());

        $this->assertSame('0.373875000', $cost->toDecimalString());
    }

    public function test_it_ignores_total_tokens_in_usage(): void
    {
        $usage = [
            'input_text_tokens' => 12,
            'input_image_tokens' => 765,
            'output_tokens' => 6_450,
            'reasoning_tokens' => 3_184,
            'total_tokens' => 10_411,
        ];

        $cost = (new ProviderCostCalculator)->calculate($usage, self::documentedRates());

        $this->assertSame('0.305510000', $cost->toDecimalString());
    }

    public function test_one_token_at_one_dollar_per_million_costs_one_microdollar(): void
    {
        $cost = (new ProviderCostCalculator)->calculate(['output_tokens' => 1], ['output_tokens' => '1.00']);

        $this->assertSame('0.000001000', $cost->toDecimalString());
    }

    public function test_zero_usage_costs_nothing(): void
    {
        $usage = array_fill_keys(array_keys(self::documentedRates()), 0);

        $cost = (new ProviderCostCalculator)->calculate($usage, self::documentedRates());

        $this->assertTrue($cost->isZero());
    }

    /**
     * @return array<string, array{array<string, mixed>, array<string, mixed>}>
     */
    public static function invalidInputs(): array
    {
        return [
            'missing usage key' => [['output_tokens' => 10], ['output_tokens' => '40.00', 'reasoning_tokens' => '12.50']],
            'negative tokens' => [['output_tokens' => -1], ['output_tokens' => '40.00']],
            'string tokens' => [['output_tokens' => '10'], ['output_tokens' => '40.00']],
            'float rate' => [['output_tokens' => 10], ['output_tokens' => 40.0]],
            'rate finer than the nano unit allows' => [['output_tokens' => 1], ['output_tokens' => '0.0001']],
            'no rates' => [['output_tokens' => 1], []],
        ];
    }

    /**
     * @param  array<string, mixed>  $usage
     * @param  array<string, mixed>  $rates
     */
    #[DataProvider('invalidInputs')]
    public function test_it_rejects_inputs_that_cannot_be_billed_exactly(array $usage, array $rates): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ProviderCostCalculator)->calculate($usage, $rates);
    }
}

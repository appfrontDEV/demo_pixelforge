<?php

namespace App\Money;

use InvalidArgumentException;

/**
 * Computes what Pixelforge bills for a generation: Σ(tokens × rate per 1M tokens).
 *
 * Uses the generation's own `rates` (fixed at creation), never hard-coded prices, and
 * iterates the rate keys so `usage.total_tokens` is never billed.
 */
final class ProviderCostCalculator
{
    private const int TOKENS_PER_RATE_UNIT = 1_000_000;

    /**
     * Input:
     *  - $usage: token counts keyed by token type, e.g. ['output_tokens' => 6450, 'total_tokens' => …]
     *  - $rates: USD per 1M tokens as decimal strings, e.g. ['output_tokens' => '40.00']
     * Output: the exact cost. Throws when a rated token type is missing or invalid, or when the
     * result cannot be expressed exactly in nano-USD.
     *
     * @param  array<string, mixed>  $usage
     * @param  array<string, mixed>  $rates
     */
    public function calculate(array $usage, array $rates): NanoUsd
    {
        if ($rates === []) {
            throw new InvalidArgumentException('Cannot calculate a cost without rates.');
        }

        $nanosTimesMillion = 0;

        foreach ($rates as $tokenType => $rate) {
            $nanosTimesMillion += $this->tokenCount($usage, $tokenType) * $this->rateInNanos($tokenType, $rate);
        }

        if ($nanosTimesMillion % self::TOKENS_PER_RATE_UNIT !== 0) {
            throw new InvalidArgumentException('Rates are more precise than nano-USD can represent exactly.');
        }

        return NanoUsd::fromNanos(intdiv($nanosTimesMillion, self::TOKENS_PER_RATE_UNIT));
    }

    /**
     * The non-negative integer token count for a rated token type.
     *
     * @param  array<string, mixed>  $usage
     */
    private function tokenCount(array $usage, string $tokenType): int
    {
        $tokens = $usage[$tokenType] ?? null;

        if (! is_int($tokens) || $tokens < 0) {
            throw new InvalidArgumentException("Usage for [{$tokenType}] must be a non-negative integer.");
        }

        return $tokens;
    }

    /**
     * The rate for 1M tokens, in nano-USD. Rates must be decimal strings, never floats.
     */
    private function rateInNanos(string $tokenType, mixed $rate): int
    {
        if (! is_string($rate)) {
            throw new InvalidArgumentException("Rate for [{$tokenType}] must be a decimal string.");
        }

        return NanoUsd::fromDecimalString($rate)->nanos;
    }
}

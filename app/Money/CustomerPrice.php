<?php

namespace App\Money;

use InvalidArgumentException;

/**
 * Turns a provider cost into what the customer pays.
 *
 * The price is kept exact, in nano-USD (provider cost × 125%), on every generation. Rounding to
 * whole cents happens once, on a total (a sum of exact prices), never per generation, so many
 * cheap generations are not each rounded up. A zero cost (failed generation) is free.
 */
final class CustomerPrice
{
    public const int MARKUP_PERCENT = 125;

    private const int NANOS_PER_CENT = NanoUsd::NANOS_PER_DOLLAR / Cents::CENTS_PER_DOLLAR;

    /**
     * Input: the exact provider cost (must not be negative).
     * Output: the exact customer price in nano-USD, ceil(cost × MARKUP_PERCENT / 100).
     * With Pixelforge's rates costs are multiples of 5,000 nanos, so the ceil never applies in
     * practice; it only guards against a future rate that would leave a fraction of a nano.
     */
    public static function exactFromProviderCost(NanoUsd $providerCost): NanoUsd
    {
        if ($providerCost->isNegative()) {
            throw new InvalidArgumentException('A provider cost cannot be negative.');
        }

        return NanoUsd::fromNanos(self::ceilDiv($providerCost->nanos * self::MARKUP_PERCENT, 100));
    }

    /**
     * Input: an exact price or a sum of exact prices (must not be negative).
     * Output: that amount rounded UP to whole cents, so anything above zero is at least 1 cent.
     */
    public static function roundUpToCents(NanoUsd $price): Cents
    {
        if ($price->isNegative()) {
            throw new InvalidArgumentException('A price cannot be negative.');
        }

        return Cents::fromCents(self::ceilDiv($price->nanos, self::NANOS_PER_CENT));
    }

    /**
     * The total in whole cents for a total provider cost: roundUpToCents(exactFromProviderCost()).
     * Pass a sum of costs, not a single generation's cost, to round only once.
     */
    public static function fromProviderCost(NanoUsd $providerCost): Cents
    {
        return self::roundUpToCents(self::exactFromProviderCost($providerCost));
    }

    private static function ceilDiv(int $numerator, int $denominator): int
    {
        return intdiv($numerator + $denominator - 1, $denominator);
    }
}

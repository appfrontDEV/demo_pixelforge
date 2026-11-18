<?php

namespace App\Money;

use InvalidArgumentException;

/**
 * Turns a provider cost into what the customer pays.
 *
 * Price = provider cost × 125%, rounded UP to whole cents in one integer step, so any
 * non-zero cost is charged at least 1 cent and a zero cost (failed generation) is free.
 */
final class CustomerPrice
{
    public const int MARKUP_PERCENT = 125;

    private const int NANOS_PER_CENT = NanoUsd::NANOS_PER_DOLLAR / Cents::CENTS_PER_DOLLAR;

    /**
     * Input: the exact provider cost (must not be negative).
     * Output: the customer price in cents, i.e. ceil(cost × MARKUP_PERCENT / 100) in cents.
     */
    public static function fromProviderCost(NanoUsd $providerCost): Cents
    {
        if ($providerCost->isNegative()) {
            throw new InvalidArgumentException('A provider cost cannot be negative.');
        }

        $numerator = $providerCost->nanos * self::MARKUP_PERCENT;
        $denominator = 100 * self::NANOS_PER_CENT;

        return Cents::fromCents(intdiv($numerator + $denominator - 1, $denominator));
    }
}

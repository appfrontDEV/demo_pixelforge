<?php

namespace App\Money;

use InvalidArgumentException;

/**
 * An exact amount of US dollars in nano-USD (1 nano-USD = $0.000000001).
 *
 * Used for provider (Pixelforge) costs, which the API reports as exact decimal strings with
 * nine decimals. Stored as an integer so sums never drift; never construct it from a float.
 */
final readonly class NanoUsd
{
    public const int NANOS_PER_DOLLAR = 1_000_000_000;

    private const int DECIMALS = 9;

    /**
     * Largest number of whole-dollar digits that still fits in a 64-bit integer of nanos.
     */
    private const int MAX_WHOLE_DIGITS = 9;

    private function __construct(public int $nanos) {}

    /**
     * Wrap an integer amount of nano-USD.
     */
    public static function fromNanos(int $nanos): self
    {
        return new self($nanos);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    /**
     * Parse a plain decimal dollar string such as "0.373875000" or "12.50".
     *
     * Input: optional "-", digits, optional "." followed by 1 to 9 digits.
     * Output: the exact amount. Throws on anything else (exponents, spaces, extra decimals).
     */
    public static function fromDecimalString(string $decimal): self
    {
        if (preg_match('/^(-?)(\d+)(?:\.(\d{1,9}))?$/', $decimal, $parts) !== 1) {
            throw new InvalidArgumentException("Not an exact dollar amount: [{$decimal}].");
        }

        if (strlen(ltrim($parts[2], '0')) > self::MAX_WHOLE_DIGITS) {
            throw new InvalidArgumentException("Dollar amount out of range: [{$decimal}].");
        }

        $fraction = str_pad($parts[3] ?? '', self::DECIMALS, '0');
        $nanos = (int) $parts[2] * self::NANOS_PER_DOLLAR + (int) $fraction;

        return new self($parts[1] === '-' ? -$nanos : $nanos);
    }

    /**
     * Format as the Pixelforge money string: dollars with exactly nine decimals, e.g. "0.373875000".
     */
    public function toDecimalString(): string
    {
        $absolute = abs($this->nanos);
        $sign = $this->nanos < 0 ? '-' : '';
        $fraction = str_pad((string) ($absolute % self::NANOS_PER_DOLLAR), self::DECIMALS, '0', STR_PAD_LEFT);

        return $sign.intdiv($absolute, self::NANOS_PER_DOLLAR).'.'.$fraction;
    }

    public function add(self $other): self
    {
        return new self($this->nanos + $other->nanos);
    }

    public function subtract(self $other): self
    {
        return new self($this->nanos - $other->nanos);
    }

    public function equals(self $other): bool
    {
        return $this->nanos === $other->nanos;
    }

    public function isLessThan(self $other): bool
    {
        return $this->nanos < $other->nanos;
    }

    public function isZero(): bool
    {
        return $this->nanos === 0;
    }

    public function isNegative(): bool
    {
        return $this->nanos < 0;
    }
}

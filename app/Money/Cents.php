<?php

namespace App\Money;

/**
 * An amount charged to customers, in whole US cents ($100 = 10000).
 *
 * Stored as an integer; display strings are built with integer math only.
 */
final readonly class Cents
{
    public const int CENTS_PER_DOLLAR = 100;

    private function __construct(public int $cents) {}

    /**
     * Wrap an integer amount of cents.
     */
    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    /**
     * Format for people, e.g. 123456789 -> "$1,234,567.89", -47 -> "-$0.47".
     */
    public function format(): string
    {
        $sign = $this->cents < 0 ? '-' : '';

        return $sign.'$'.$this->groupThousands($this->wholeDollars()).'.'.$this->centsPart();
    }

    /**
     * Format as a plain decimal string for APIs, e.g. 47 -> "0.47", -1005 -> "-10.05".
     */
    public function toDecimalString(): string
    {
        $sign = $this->cents < 0 ? '-' : '';

        return $sign.$this->wholeDollars().'.'.$this->centsPart();
    }

    public function add(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(self $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents;
    }

    public function isLessThan(self $other): bool
    {
        return $this->cents < $other->cents;
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    /**
     * Whole dollars of the absolute amount, as digits.
     */
    private function wholeDollars(): string
    {
        return (string) intdiv(abs($this->cents), self::CENTS_PER_DOLLAR);
    }

    /**
     * The two cent digits of the absolute amount.
     */
    private function centsPart(): string
    {
        return str_pad((string) (abs($this->cents) % self::CENTS_PER_DOLLAR), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Insert "," every three digits from the right, e.g. "1234567" -> "1,234,567".
     */
    private function groupThousands(string $digits): string
    {
        return strrev(implode(',', str_split(strrev($digits), 3)));
    }
}

<?php

namespace App\Casts;

use App\Money\Cents;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Casts a BIGINT cents column to {@see Cents}.
 *
 * @implements CastsAttributes<Cents, Cents|int>
 */
class AsCents implements CastsAttributes
{
    use ReadsStoredIntegers;

    /**
     * Stored int (or digit string) -> Cents, null stays null.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Cents
    {
        $cents = $this->storedInteger($key, $value);

        return $cents === null ? null : Cents::fromCents($cents);
    }

    /**
     * Cents or int -> stored int, null stays null. Anything else (floats included) throws.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value instanceof Cents) {
            return $value->cents;
        }

        return $this->writableInteger($key, $value);
    }
}

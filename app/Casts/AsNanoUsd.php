<?php

namespace App\Casts;

use App\Money\NanoUsd;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Casts a BIGINT nano-USD column to {@see NanoUsd}.
 *
 * Serializes to the Pixelforge nine-decimal string ("0.373875000") so JSON never carries
 * large integers that JavaScript would round.
 *
 * @implements CastsAttributes<NanoUsd, NanoUsd|int>
 */
class AsNanoUsd implements CastsAttributes, SerializesCastableAttributes
{
    use ReadsStoredIntegers;

    /**
     * Stored int (or digit string) -> NanoUsd, null stays null.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?NanoUsd
    {
        $nanos = $this->storedInteger($key, $value);

        return $nanos === null ? null : NanoUsd::fromNanos($nanos);
    }

    /**
     * NanoUsd or int -> stored int, null stays null. Anything else (floats included) throws.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value instanceof NanoUsd) {
            return $value->nanos;
        }

        return $this->writableInteger($key, $value);
    }

    /**
     * NanoUsd -> "0.373875000" in arrays and JSON.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function serialize(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value instanceof NanoUsd ? $value->toDecimalString() : null;
    }
}

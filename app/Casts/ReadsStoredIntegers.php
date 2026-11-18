<?php

namespace App\Casts;

use InvalidArgumentException;

/**
 * Shared integer handling for the money casts.
 */
trait ReadsStoredIntegers
{
    /**
     * Value read from the database -> int or null.
     *
     * Accepts ints and digit strings, because some drivers return BIGINT columns as strings.
     */
    protected function storedInteger(string $key, mixed $value): ?int
    {
        if ($value === null || is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        throw new InvalidArgumentException("Stored money value for [{$key}] is not an integer.");
    }

    /**
     * Value assigned in PHP -> int or null. Only real integers are accepted, never floats or strings.
     */
    protected function writableInteger(string $key, mixed $value): ?int
    {
        if ($value === null || is_int($value)) {
            return $value;
        }

        throw new InvalidArgumentException("Money value for [{$key}] must be an integer or a money object.");
    }
}

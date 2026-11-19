<?php

namespace App\Enums;

/**
 * Quality levels Pixelforge accepts. The value is sent to the provider as-is.
 */
enum ImageQuality: string
{
    case Low = 'low';

    case Medium = 'medium';

    case High = 'high';

    /**
     * The quality preselected on the prompt form.
     */
    public static function default(): self
    {
        return self::Medium;
    }
}

<?php

namespace App\Enums;

/**
 * Output sizes Pixelforge accepts. The value is sent to the provider as-is.
 */
enum ImageSize: string
{
    case Square = '1024x1024';

    case Landscape = '1536x1024';

    case Portrait = '1024x1536';

    /**
     * The size preselected on the prompt form.
     */
    public static function default(): self
    {
        return self::Square;
    }
}

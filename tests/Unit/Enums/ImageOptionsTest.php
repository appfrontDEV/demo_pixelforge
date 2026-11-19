<?php

namespace Tests\Unit\Enums;

use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use PHPUnit\Framework\TestCase;

/**
 * Test strategy: the size and quality options Pixelforge accepts (issue #1 §1.1).
 * Data: none; the enums are the data.
 * - The backed values match the provider's strings exactly, so they can be sent as-is.
 * - The defaults are 1024x1024 and medium, as specified for the prompt form.
 */
class ImageOptionsTest extends TestCase
{
    public function test_sizes_match_the_provider_values(): void
    {
        $this->assertSame(['1024x1024', '1536x1024', '1024x1536'], array_column(ImageSize::cases(), 'value'));
        $this->assertSame(ImageSize::Square, ImageSize::default());
    }

    public function test_qualities_match_the_provider_values(): void
    {
        $this->assertSame(['low', 'medium', 'high'], array_column(ImageQuality::cases(), 'value'));
        $this->assertSame(ImageQuality::Medium, ImageQuality::default());
    }
}

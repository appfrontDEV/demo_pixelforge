<?php

namespace App\Concerns;

use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The rules for a new generation, mirroring Pixelforge's own validation (POST /v1/generations),
 * so bad input is caught before anything is queued. Shared by the web, the API and the console.
 */
trait GenerationValidationRules
{
    public const int MAX_PROMPT_LENGTH = 4000;

    public const int MAX_REFERENCE_IMAGES = 4;

    /**
     * Input shape: {prompt: string, size?: string, quality?: string, reference_images?: list<string>}.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string|object>>
     */
    protected function generationRules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:'.self::MAX_PROMPT_LENGTH],
            'size' => ['nullable', Rule::enum(ImageSize::class)],
            'quality' => ['nullable', Rule::enum(ImageQuality::class)],
            'reference_images' => ['nullable', 'array', 'list', 'max:'.self::MAX_REFERENCE_IMAGES],
            'reference_images.*' => ['required', 'string', 'url:http,https'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function generationMessages(): array
    {
        return [
            'reference_images.max' => __('Add at most :max reference images.'),
            'reference_images.*.url' => __('Each reference image must be an http or https URL.'),
        ];
    }
}

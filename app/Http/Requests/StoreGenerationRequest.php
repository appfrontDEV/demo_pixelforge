<?php

namespace App\Http\Requests;

use App\Concerns\GenerationValidationRules;
use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Creating a generation (web and API).
 *
 * Input: `prompt` (required, ≤ 4000 characters), `size` (1024x1024 default, 1536x1024,
 * 1024x1536), `quality` (low, medium default, high), `reference_images` (≤ 4 http/https URLs).
 */
class StoreGenerationRequest extends FormRequest
{
    use GenerationValidationRules;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->generationRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->generationMessages();
    }

    /**
     * Output: the validated size, or the provider's default.
     */
    public function size(): ImageSize
    {
        return $this->enum('size', ImageSize::class) ?? ImageSize::default();
    }

    /**
     * Output: the validated quality, or the provider's default.
     */
    public function quality(): ImageQuality
    {
        return $this->enum('quality', ImageQuality::class) ?? ImageQuality::default();
    }

    /**
     * Output: the validated reference image URLs, possibly empty.
     *
     * @return list<string>
     */
    public function referenceImages(): array
    {
        return array_values($this->array('reference_images'));
    }
}

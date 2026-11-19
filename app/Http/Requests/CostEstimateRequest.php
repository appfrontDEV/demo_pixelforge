<?php

namespace App\Http\Requests;

use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Asking for a cost estimate (web and API).
 *
 * Input (query string): `size` (1024x1024, 1536x1024 or 1024x1536) and `quality` (low, medium
 * or high), both required.
 */
class CostEstimateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'size' => ['required', Rule::enum(ImageSize::class)],
            'quality' => ['required', Rule::enum(ImageQuality::class)],
        ];
    }

    /**
     * Output: the validated size.
     */
    public function size(): ImageSize
    {
        return $this->enum('size', ImageSize::class) ?? ImageSize::default();
    }

    /**
     * Output: the validated quality.
     */
    public function quality(): ImageQuality
    {
        return $this->enum('quality', ImageQuality::class) ?? ImageQuality::default();
    }
}

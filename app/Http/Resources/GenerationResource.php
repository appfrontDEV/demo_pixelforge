<?php

namespace App\Http\Resources;

use App\Models\Generation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A generation as the owner sees it. The Pixelforge id and raw usage stay internal.
 *
 * @mixin Generation
 */
class GenerationResource extends JsonResource
{
    /**
     * Output: {id, status, prompt, size, quality, reference_images, estimate_min_cents,
     * estimate_max_cents, price_usd, image_url, image_url_expires_at, error, created_at,
     * completed_at}. price_usd is the exact price as a decimal string (9 decimals) once known;
     * error is null or {code, message, fields}, where fields maps a parameter to its problems.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'prompt' => $this->prompt,
            'size' => $this->size->value,
            'quality' => $this->quality->value,
            'reference_images' => $this->reference_images ?? [],
            'estimate_min_cents' => $this->estimate_min_cents?->cents,
            'estimate_max_cents' => $this->estimate_max_cents?->cents,
            'price_usd' => $this->price_nusd?->toDecimalString(),
            'image_url' => $this->image_url,
            'image_url_expires_at' => $this->image_url_expires_at?->toIso8601String(),
            'error' => $this->errorDetails(),
            'created_at' => $this->created_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }

    /**
     * Output: null unless the generation failed, else {code, message, fields}.
     *
     * @return array{code: string, message: string|null, fields: array<string, list<string>>}|null
     */
    private function errorDetails(): ?array
    {
        if ($this->error_code === null) {
            return null;
        }

        return ['code' => $this->error_code, 'message' => $this->error_message, 'fields' => $this->error_fields ?? []];
    }
}

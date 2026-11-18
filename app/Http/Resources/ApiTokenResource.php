<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Public metadata of an API token. Never includes the secret.
 *
 * @mixin PersonalAccessToken
 */
class ApiTokenResource extends JsonResource
{
    /**
     * Output: {id, name, last_used_at, created_at}, timestamps as ISO 8601 strings or null.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

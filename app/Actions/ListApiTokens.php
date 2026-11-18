<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Lists a user's API tokens. Shared by the settings page, the API and the console.
 */
class ListApiTokens
{
    /**
     * Input: the token owner.
     * Output: their tokens, newest first. Only metadata: secrets are stored hashed and never returned.
     *
     * @return Collection<int, PersonalAccessToken>
     */
    public function handle(User $user): Collection
    {
        return $user->tokens()->latest('id')->get();
    }
}

<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Revokes (deletes) one of a user's API tokens. Shared by the settings page, the API and the console.
 */
class RevokeApiToken
{
    /**
     * Input: the token owner and the token id.
     * Output: none. Throws ModelNotFoundException (404 over HTTP) when the id does not exist or
     * belongs to someone else, so a user can only ever revoke their own tokens.
     *
     * @throws ModelNotFoundException
     */
    public function handle(User $user, int $tokenId): void
    {
        $user->tokens()->whereKey($tokenId)->firstOrFail()->delete();
    }
}

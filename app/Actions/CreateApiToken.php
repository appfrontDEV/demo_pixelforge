<?php

namespace App\Actions;

use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

/**
 * Issues a personal API token. Shared by the settings page, the API and the console.
 */
class CreateApiToken
{
    /**
     * Input: the token owner and a label chosen by them (e.g. "CI script").
     * Output: the new token. Its `plainTextToken` ("<id>|<secret>") is only available now;
     * the database keeps a hash, so the caller must show it to the user immediately.
     */
    public function handle(User $user, string $name): NewAccessToken
    {
        return $user->createToken($name);
    }
}

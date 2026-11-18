<?php

namespace App\Console\Commands;

use App\Actions\RevokeApiToken;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;

#[Signature('api-tokens:revoke {email : Email of the token owner} {id : Token id, from api-tokens:list}')]
#[Description("Revoke one of a user's API tokens")]
class ApiTokenRevoke extends Command
{
    /**
     * Input: owner email and token id. Output: exit 0 when revoked; exit 1 when the user does
     * not exist or the token is not theirs.
     */
    public function handle(RevokeApiToken $revokeApiToken): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->components->error('No user with that email.');

            return self::FAILURE;
        }

        try {
            $revokeApiToken->handle($user, (int) $this->argument('id'));
        } catch (ModelNotFoundException) {
            $this->components->error('That user has no token with this id.');

            return self::FAILURE;
        }

        $this->components->info('Token revoked.');

        return self::SUCCESS;
    }
}

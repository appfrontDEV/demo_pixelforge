<?php

namespace App\Console\Commands;

use App\Actions\CreateApiToken;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('api-tokens:create {email : Email of the token owner} {name : Label for the token}')]
#[Description('Create an API token for a user and print it once')]
class ApiTokenCreate extends Command
{
    /**
     * Input: owner email and token label. Output: the plain-text token on stdout; exit 1 if
     * the user does not exist.
     */
    public function handle(CreateApiToken $createApiToken): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->components->error('No user with that email.');

            return self::FAILURE;
        }

        $newToken = $createApiToken->handle($user, (string) $this->argument('name'));

        $this->components->info('Token created. Copy it now: it will not be shown again.');
        $this->line($newToken->plainTextToken);

        return self::SUCCESS;
    }
}

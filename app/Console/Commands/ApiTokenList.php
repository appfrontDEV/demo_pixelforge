<?php

namespace App\Console\Commands;

use App\Actions\ListApiTokens;
use App\Http\Resources\ApiTokenResource;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('api-tokens:list {email : Email of the token owner}')]
#[Description("List a user's API tokens (metadata only)")]
class ApiTokenList extends Command
{
    /**
     * Input: owner email. Output: a table of id, name, last used and created; exit 1 if the
     * user does not exist.
     */
    public function handle(ListApiTokens $listApiTokens): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->components->error('No user with that email.');

            return self::FAILURE;
        }

        $this->table(
            ['ID', 'Name', 'Last used', 'Created'],
            ApiTokenResource::collection($listApiTokens->handle($user))->resolve(),
        );

        return self::SUCCESS;
    }
}

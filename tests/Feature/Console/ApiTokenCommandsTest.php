<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test strategy: the api-tokens:* console commands, the console side of token management.
 * Data: factory users with known emails; tokens created through the model.
 * - create prints a working token; list shows the user's tokens; revoke deletes one.
 * - An unknown email, or a token id owned by someone else, exits with failure and changes nothing.
 */
class ApiTokenCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_prints_a_new_token(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $this->artisan('api-tokens:create', ['email' => 'ada@example.com', 'name' => 'CI script'])
            ->expectsOutputToContain('will not be shown again')
            ->assertSuccessful();

        $this->assertSame(['CI script'], $user->tokens()->pluck('name')->all());
    }

    public function test_list_shows_the_users_tokens(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);
        $token = $user->createToken('CI script')->accessToken;

        $this->artisan('api-tokens:list', ['email' => 'ada@example.com'])
            ->expectsTable(
                ['ID', 'Name', 'Last used', 'Created'],
                [[$token->id, 'CI script', null, $token->created_at?->toIso8601String()]],
            )
            ->assertSuccessful();
    }

    public function test_revoke_deletes_the_users_token(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);
        $token = $user->createToken('CI script')->accessToken;

        $this->artisan('api-tokens:revoke', ['email' => 'ada@example.com', 'id' => $token->id])
            ->assertSuccessful();

        $this->assertModelMissing($token);
    }

    public function test_revoke_refuses_someone_elses_token(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);
        $theirs = User::factory()->create()->createToken('theirs')->accessToken;

        $this->artisan('api-tokens:revoke', ['email' => 'ada@example.com', 'id' => $theirs->id])
            ->assertFailed();

        $this->assertModelExists($theirs);
    }

    public function test_commands_fail_for_an_unknown_email(): void
    {
        $this->artisan('api-tokens:create', ['email' => 'nobody@example.com', 'name' => 'x'])->assertFailed();
        $this->artisan('api-tokens:list', ['email' => 'nobody@example.com'])->assertFailed();
        $this->artisan('api-tokens:revoke', ['email' => 'nobody@example.com', 'id' => 1])->assertFailed();
    }
}

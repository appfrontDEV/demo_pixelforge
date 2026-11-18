<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

/**
 * Test strategy: the API tokens settings page, which uses the same actions as the API and console.
 * Data: factory users; tokens created through the model.
 * - The page lists only the signed-in user's tokens, as metadata (no secrets); guests are redirected.
 * - Creating validates `name`, stores a token and flashes the plain-text value once as `newToken`.
 * - Revoking deletes the user's own token; someone else's token is 404 and survives.
 */
class ApiTokensTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_lists_only_the_users_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('mine');
        User::factory()->create()->createToken('theirs');

        $this->actingAs($user)
            ->get(route('api-tokens.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/ApiTokens')
                ->has('tokens', 1)
                ->has('tokens.0', fn (Assert $token) => $token
                    ->where('name', 'mine')
                    ->hasAll(['id', 'last_used_at', 'created_at'])
                    ->missing('token'),
                ),
            );
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('api-tokens.index'))->assertRedirect(route('login'));
    }

    public function test_creating_a_token_flashes_it_once(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('api-tokens.index'))
            ->post(route('api-tokens.store'), ['name' => 'CI script'])
            ->assertRedirect(route('api-tokens.index'))
            ->assertInertiaFlash('newToken');

        $flashedToken = session(SessionKey::FLASH_DATA)['newToken'];
        $this->assertTrue(PersonalAccessToken::findToken($flashedToken)?->tokenable?->is($user));
        $this->assertSame(['CI script'], $user->tokens()->pluck('name')->all());
    }

    public function test_creating_requires_a_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('api-tokens.store'), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_a_user_can_revoke_their_own_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mine')->accessToken;

        $this->actingAs($user)
            ->from(route('api-tokens.index'))
            ->delete(route('api-tokens.destroy', $token->id))
            ->assertRedirect(route('api-tokens.index'));

        $this->assertModelMissing($token);
    }

    public function test_revoking_someone_elses_token_is_not_found(): void
    {
        $theirs = User::factory()->create()->createToken('theirs')->accessToken;

        $this->actingAs(User::factory()->create())
            ->delete(route('api-tokens.destroy', $theirs->id))
            ->assertNotFound();

        $this->assertModelExists($theirs);
    }
}

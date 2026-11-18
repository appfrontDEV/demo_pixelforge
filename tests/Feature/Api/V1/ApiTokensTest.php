<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

/**
 * Test strategy: /api/v1/tokens, the API side of token management (documented in docs/openapi.yaml).
 * Data: a user created by the factory with password "password"; tokens created through the model.
 * - POST exchanges valid credentials for a token (201, {data: {id, name, ..., token}}) that then
 *   authenticates; bad credentials and missing fields give 422 {message, errors}; it is throttled
 *   by Fortify's login limiter (5/min per email + IP).
 * - GET lists only the caller's tokens, never secrets; unauthenticated calls get a JSON 401,
 *   even without an Accept header (no redirect to /login).
 * - DELETE revokes the caller's own token (204); someone else's token is 404 and survives.
 */
class ApiTokensTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_credentials_issue_a_working_token(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('api.v1.tokens.store'), [
            'email' => $user->email,
            'password' => 'password',
            'name' => 'CI script',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'name', 'last_used_at', 'created_at', 'token']])
            ->assertJsonPath('data.name', 'CI script');

        $this->withToken($response->json('data.token'))
            ->getJson(route('api.v1.tokens.index'))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'CI script');
    }

    public function test_wrong_password_and_unknown_email_get_the_same_validation_error(): void
    {
        $user = User::factory()->create();

        foreach ([[$user->email, 'wrong-password'], ['nobody@example.com', 'password']] as [$email, $password]) {
            $this->postJson(route('api.v1.tokens.store'), ['email' => $email, 'password' => $password, 'name' => 'x'])
                ->assertUnprocessable()
                ->assertJsonStructure(['message', 'errors' => ['email']])
                ->assertJsonPath('errors.email.0', __('auth.failed'));
        }

        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_issuing_requires_all_fields(): void
    {
        $this->postJson(route('api.v1.tokens.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password', 'name']);
    }

    public function test_issuing_is_throttled_per_email_like_login(): void
    {
        $payload = ['email' => 'nobody@example.com', 'password' => 'x', 'name' => 'x'];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson(route('api.v1.tokens.store'), $payload)->assertUnprocessable();
        }

        $this->postJson(route('api.v1.tokens.store'), $payload)->assertTooManyRequests();
    }

    public function test_listing_shows_only_the_callers_tokens_without_secrets(): void
    {
        $user = User::factory()->create();
        $plainTextToken = $user->createToken('mine')->plainTextToken;
        User::factory()->create()->createToken('theirs');

        $this->withToken($plainTextToken)
            ->getJson(route('api.v1.tokens.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'mine')
            ->assertJsonMissingPath('data.0.token');
    }

    public function test_unauthenticated_requests_get_json_401_without_accept_header(): void
    {
        $this->get(route('api.v1.tokens.index'))
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_a_user_can_revoke_their_own_token(): void
    {
        $user = User::factory()->create();
        $plainTextToken = $user->createToken('caller')->plainTextToken;
        $other = $user->createToken('to revoke')->accessToken;

        $this->withToken($plainTextToken)
            ->deleteJson(route('api.v1.tokens.destroy', $other->id))
            ->assertNoContent();

        $this->assertModelMissing($other);
    }

    public function test_revoking_someone_elses_token_is_not_found(): void
    {
        $plainTextToken = User::factory()->create()->createToken('caller')->plainTextToken;
        $theirs = User::factory()->create()->createToken('theirs')->accessToken;

        $this->withToken($plainTextToken)
            ->deleteJson(route('api.v1.tokens.destroy', $theirs->id))
            ->assertNotFound()
            ->assertJsonStructure(['message']);

        $this->assertModelExists($theirs);
    }
}

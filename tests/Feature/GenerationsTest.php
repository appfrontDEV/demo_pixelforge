<?php

namespace Tests\Feature;

use App\Enums\GenerationStatus;
use App\Jobs\SubmitGeneration;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Test strategy: POST /generations, the web (session) side of creating a generation. The prompt
 * form that posts here arrives with #10.
 * Data: a verified factory user; no baselines seeded, so the estimate is the seed range; the
 * queue is faked.
 * - Valid input stores a pending row for the user, queues its submission, and redirects back
 *   with a success toast.
 * - Invalid input redirects back with session errors on the field; nothing is stored.
 * - Guests are redirected to login.
 */
class GenerationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    public function test_signed_in_users_can_create_a_generation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('generations.store'), ['prompt' => 'A lighthouse at dawn', 'quality' => 'low'])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasNoErrors();

        $generation = Generation::query()->sole();
        $this->assertTrue($generation->user?->is($user));
        $this->assertSame(GenerationStatus::Pending, $generation->status);
        $this->assertSame(100_000, $generation->estimate_max_cents?->cents);
        Queue::assertPushed(SubmitGeneration::class, 1);
    }

    public function test_invalid_input_returns_field_errors(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('dashboard'))
            ->post(route('generations.store'), ['prompt' => '', 'size' => 'huge'])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors(['prompt', 'size']);

        $this->assertSame(0, Generation::count());
        Queue::assertNothingPushed();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->post(route('generations.store'), ['prompt' => 'x'])->assertRedirect(route('login'));

        $this->assertSame(0, Generation::count());
    }
}

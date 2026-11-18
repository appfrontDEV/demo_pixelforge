<?php

namespace Tests\Feature\Models;

use App\Enums\GenerationStatus;
use App\Models\Generation;
use App\Models\User;
use App\Money\Cents;
use App\Money\NanoUsd;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Tests\TestCase;
use Tests\Unit\Enums\GenerationStatusTest;

/**
 * Test strategy: Generation::transitionTo() is the only way to change status.
 * - Every (from, to) pair from the explicit table in GenerationStatusTest, against the database: the return value and the stored status.
 * - Repeats are idempotent: same status → false, nothing written (updated_at unchanged).
 * - The guard is in the UPDATE itself: a stale copy cannot move a final row back.
 * - Extra attributes are written with the move, through casts, and only when it moves.
 */
class GenerationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProviderExternal(GenerationStatusTest::class, 'transitions')]
    public function test_transition_only_moves_forward(GenerationStatus $from, GenerationStatus $to, bool $allowed): void
    {
        $generation = Generation::factory()->create(['status' => $from]);

        $moved = $generation->transitionTo($to);

        $expected = $allowed ? $to : $from;
        $this->assertSame($allowed, $moved);
        $this->assertSame($expected, $generation->status);
        $this->assertSame($expected, $generation->fresh()?->status);
    }

    public function test_repeating_a_transition_is_idempotent(): void
    {
        Carbon::setTestNow('2026-11-18 10:00:00');
        $generation = Generation::factory()->create();

        $this->assertTrue($generation->transitionTo(GenerationStatus::Succeeded, ['price_cents' => 47]));

        Carbon::setTestNow('2026-11-18 10:05:00');
        $this->assertFalse($generation->transitionTo(GenerationStatus::Succeeded, ['price_cents' => 99]));

        $stored = $generation->fresh();
        $this->assertSame(GenerationStatus::Succeeded, $stored?->status);
        $this->assertSame(47, $stored->price_cents?->cents);
        $this->assertEquals(Carbon::parse('2026-11-18 10:00:00'), $stored->completed_at);
        $this->assertEquals(Carbon::parse('2026-11-18 10:00:00'), $stored->updated_at);
    }

    public function test_a_stale_copy_cannot_move_a_final_generation_back(): void
    {
        $generation = Generation::factory()->queued()->create();
        $stale = Generation::findOrFail($generation->id);

        $this->assertTrue($generation->transitionTo(GenerationStatus::Succeeded));

        // The stale copy still thinks the row is queued, so a late "processing" looks forward to it.
        $this->assertFalse($stale->transitionTo(GenerationStatus::Processing));
        $this->assertSame(GenerationStatus::Succeeded, $stale->status);
        $this->assertSame(GenerationStatus::Succeeded, $generation->fresh()?->status);
    }

    public function test_a_final_state_cannot_flip_to_the_other_final_state(): void
    {
        $generation = Generation::factory()->processing()->create();
        $stale = Generation::findOrFail($generation->id);

        $this->assertTrue($generation->transitionTo(GenerationStatus::Failed, ['error_code' => 'generation_failed']));
        $this->assertFalse($stale->transitionTo(GenerationStatus::Succeeded, ['price_cents' => 47]));

        $stored = $generation->fresh();
        $this->assertSame(GenerationStatus::Failed, $stored?->status);
        $this->assertNull($stored->price_cents);
    }

    public function test_it_writes_attributes_through_casts_with_the_transition(): void
    {
        $generation = Generation::factory()->processing()->create();
        $completedAt = Carbon::parse('2026-11-18 09:30:00');

        $moved = $generation->transitionTo(GenerationStatus::Succeeded, [
            'usage' => ['output_tokens' => 6_450],
            'provider_cost_nusd' => NanoUsd::fromDecimalString('0.373875000'),
            'price_cents' => Cents::fromCents(47),
            'completed_at' => $completedAt,
        ]);

        $this->assertTrue($moved);
        $this->assertFalse($generation->isDirty());

        $stored = $generation->fresh();
        $this->assertSame(['output_tokens' => 6_450], $stored?->usage);
        $this->assertSame('0.373875000', $stored->provider_cost_nusd?->toDecimalString());
        $this->assertSame(47, $stored->price_cents?->cents);
        $this->assertEquals($completedAt, $stored->completed_at);
    }

    public function test_it_stamps_the_lifecycle_timestamp_of_the_new_status(): void
    {
        Carbon::setTestNow('2026-11-18 10:00:00');
        $generation = Generation::factory()->create();

        $generation->transitionTo(GenerationStatus::Queued);
        $generation->transitionTo(GenerationStatus::Processing);
        Carbon::setTestNow('2026-11-18 10:01:00');
        $generation->transitionTo(GenerationStatus::Succeeded);

        $stored = $generation->fresh();
        $this->assertEquals(Carbon::parse('2026-11-18 10:00:00'), $stored?->submitted_at);
        $this->assertEquals(Carbon::parse('2026-11-18 10:00:00'), $stored->started_at);
        $this->assertEquals(Carbon::parse('2026-11-18 10:01:00'), $stored->completed_at);
    }

    public function test_status_cannot_be_mass_assigned(): void
    {
        $generation = Generation::factory()->create();

        $generation->fill(['status' => GenerationStatus::Succeeded->value]);

        $this->assertSame(GenerationStatus::Pending, $generation->status);
    }

    public function test_new_generations_start_pending_and_belong_to_a_user(): void
    {
        $user = User::factory()->create();

        $generation = $user->generations()->create([
            'prompt' => 'A lighthouse at dusk',
            'size' => '1024x1024',
            'quality' => 'medium',
        ]);

        $this->assertSame(GenerationStatus::Pending, $generation->fresh()?->status);
        $this->assertTrue($generation->user?->is($user));
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $generation->id);
    }

    public function test_factory_states_are_consistent(): void
    {
        $succeeded = Generation::factory()->succeeded()->create();
        $failed = Generation::factory()->failed()->create();

        $this->assertSame(GenerationStatus::Succeeded, $succeeded->status);
        $this->assertSame('0.373875000', $succeeded->provider_cost_nusd?->toDecimalString());
        $this->assertSame(GenerationStatus::Failed, $failed->status);
        $this->assertSame(0, $failed->price_cents?->cents);
    }
}

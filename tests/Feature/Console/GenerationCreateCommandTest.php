<?php

namespace Tests\Feature\Console;

use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Jobs\SubmitGeneration;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Test strategy: the generations:create console command, the console side of creating a
 * generation. Same rules and action as the web and the API.
 * Data: a factory user with a known email; the queue is faked.
 * - Stores a pending row for that user with the given options, queues it, prints its id.
 * - Defaults to 1024x1024 / medium; --reference-image can be repeated.
 * - An unknown email or invalid options exit with failure and store nothing.
 */
class GenerationCreateCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    public function test_it_creates_and_queues_a_generation(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $this->artisan('generations:create', [
            'email' => 'ada@example.com',
            'prompt' => 'A lighthouse at dawn',
            '--size' => '1024x1536',
            '--quality' => 'high',
            '--reference-image' => ['https://example.com/a.png', 'https://example.com/b.png'],
        ])->expectsOutputToContain('Generation queued')->assertSuccessful();

        $generation = Generation::query()->sole();
        $this->assertTrue($generation->user?->is($user));
        $this->assertSame(ImageSize::Portrait, $generation->size);
        $this->assertSame(ImageQuality::High, $generation->quality);
        $this->assertSame(['https://example.com/a.png', 'https://example.com/b.png'], $generation->reference_images);
        Queue::assertPushed(SubmitGeneration::class, 1);
    }

    public function test_options_default_like_the_provider(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->artisan('generations:create', ['email' => 'ada@example.com', 'prompt' => 'x'])->assertSuccessful();

        $generation = Generation::query()->sole();
        $this->assertSame([ImageSize::Square, ImageQuality::Medium], [$generation->size, $generation->quality]);
        $this->assertSame([], $generation->reference_images);
    }

    public function test_invalid_options_and_unknown_emails_fail(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->artisan('generations:create', ['email' => 'ada@example.com', 'prompt' => 'x', '--size' => 'huge'])
            ->expectsOutputToContain('size')
            ->assertFailed();
        $this->artisan('generations:create', ['email' => 'nobody@example.com', 'prompt' => 'x'])->assertFailed();

        $this->assertSame(0, Generation::count());
        Queue::assertNothingPushed();
    }
}

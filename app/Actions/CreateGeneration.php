<?php

namespace App\Actions;

use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Jobs\SubmitGeneration;
use App\Models\Generation;
use App\Models\User;

/**
 * Starts a generation. Shared by the web, the API and the console.
 *
 * The local row is written first, so it exists (with its UUID) before Pixelforge hears about it.
 * The UUID is then the Idempotency-Key and `metadata.local_id` of the queued submission.
 */
class CreateGeneration
{
    public function __construct(private readonly EstimateCost $estimateCost) {}

    /**
     * Input: the owner and already validated options; $referenceImages may be empty.
     * Output: the new generation, `pending`, carrying the current estimate in cents. Its
     * SubmitGeneration job is queued.
     *
     * @param  list<string>  $referenceImages
     */
    public function handle(User $user, string $prompt, ImageSize $size, ImageQuality $quality, array $referenceImages): Generation
    {
        $estimate = $this->estimateCost->handle($size, $quality);

        $generation = $user->generations()->create([
            'prompt' => $prompt,
            'size' => $size,
            'quality' => $quality,
            'reference_images' => $referenceImages,
            'estimate_min_cents' => $estimate->min_cents,
            'estimate_max_cents' => $estimate->max_cents,
        ]);

        SubmitGeneration::dispatch($generation)->afterCommit();

        return $generation;
    }
}

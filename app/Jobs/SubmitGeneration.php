<?php

namespace App\Jobs;

use App\Enums\GenerationStatus;
use App\Models\Generation;
use App\Services\Pixelforge\Exceptions\PixelforgeException;
use App\Services\Pixelforge\Exceptions\RetryableException;
use App\Services\Pixelforge\PixelforgeClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Sends a pending generation to Pixelforge (POST /v1/generations) and records the answer.
 *
 * Safe to run more than once: the Idempotency-Key is the local UUID, so a retry after a lost
 * response gets the same provider generation back, and a row that has left `pending` is skipped.
 * Retry pacing (Retry-After, limits) is refined in #9.
 */
class SubmitGeneration implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Statuses reported by the provider that this job may apply. A final status is never applied
     * here, even on a replay that reports one: it must come with usage and cost (#13, #14).
     *
     * @var array<string, GenerationStatus>
     */
    private const array APPLICABLE_STATUSES = [
        'queued' => GenerationStatus::Queued,
        'processing' => GenerationStatus::Processing,
        'succeeded' => GenerationStatus::Processing,
        'failed' => GenerationStatus::Processing,
    ];

    /**
     * Input: a pending generation, saved locally.
     */
    public function __construct(public Generation $generation) {}

    /**
     * Seconds to wait before each retry.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [2, 10, 30];
    }

    /**
     * Output: none. Accepted: provider_id, rates and status are stored. Permanently rejected
     * (validation, content policy, …): the row fails at $0. Retryable: the exception is rethrown
     * so the queue tries again, and the row stays pending.
     *
     * @throws PixelforgeException when the error is worth retrying
     */
    public function handle(PixelforgeClient $client): void
    {
        if ($this->generation->status !== GenerationStatus::Pending) {
            return;
        }

        try {
            $accepted = $client->createGeneration($this->payload(), $this->generation->id);
        } catch (PixelforgeException $exception) {
            $this->isRetryable($exception) ? throw $exception : $this->markFailed($exception);

            return;
        }

        $this->storeAccepted($accepted);
    }

    /**
     * Called by the queue when every attempt failed: the row fails at $0 instead of staying pending.
     */
    public function failed(?Throwable $exception): void
    {
        $this->markFailed($exception);
    }

    /**
     * Output: the documented POST /v1/generations body. `reference_images` is left out when
     * empty, and `metadata.local_id` lets webhooks be matched to this row.
     *
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $referenceImages = $this->generation->reference_images ?? [];

        return [
            'prompt' => $this->generation->prompt,
            'size' => $this->generation->size->value,
            'quality' => $this->generation->quality->value,
            ...($referenceImages === [] ? [] : ['reference_images' => $referenceImages]),
            'metadata' => ['local_id' => $this->generation->id],
        ];
    }

    /**
     * Input: the generation object from the 202 response. Stores provider_id and rates (unless a
     * webhook already did), then moves the row forward; never backwards, never to a final state.
     *
     * @param  array<string, mixed>  $accepted
     */
    private function storeAccepted(array $accepted): void
    {
        if ($this->generation->provider_id === null) {
            $this->generation->forceFill(['provider_id' => $accepted['id'], 'rates' => $accepted['rates'] ?? null])->save();
        }

        $status = is_string($accepted['status'] ?? null) ? $accepted['status'] : 'queued';
        $this->generation->transitionTo(self::APPLICABLE_STATUSES[$status] ?? GenerationStatus::Queued);
    }

    /**
     * Input: why the submission failed. The row moves to `failed` at $0 with the error code,
     * message and, for a provider validation error, the problems per field.
     */
    private function markFailed(?Throwable $exception): void
    {
        $isProviderError = $exception instanceof PixelforgeException;

        $this->generation->transitionTo(GenerationStatus::Failed, [
            'error_code' => $isProviderError ? $exception->errorCode : 'submission_failed',
            'error_message' => $isProviderError ? $exception->getMessage() : __('The generation could not be submitted.'),
            'error_fields' => $isProviderError && $exception->errors !== [] ? $exception->errors : null,
            'provider_cost_nusd' => 0,
            'price_nusd' => 0,
        ]);
    }

    /**
     * Retry what may succeed later: documented retryable errors and server errors. Retrying is
     * safe because the Idempotency-Key makes a repeat return the same generation.
     */
    private function isRetryable(PixelforgeException $exception): bool
    {
        return $exception instanceof RetryableException || ($exception->httpStatus ?? 0) >= 500;
    }
}

<?php

namespace App\Enums;

/**
 * Lifecycle of a generation. It only ever moves forward:
 *
 *   pending → queued → processing → succeeded | failed
 *
 * Steps may be skipped (a webhook can report `succeeded` before our create call returns),
 * but a status never moves back, and `succeeded` / `failed` are final.
 */
enum GenerationStatus: string
{
    /** Saved locally, not yet accepted by Pixelforge. */
    case Pending = 'pending';

    /** Accepted by Pixelforge, waiting for a worker. */
    case Queued = 'queued';

    case Processing = 'processing';

    case Succeeded = 'succeeded';

    case Failed = 'failed';

    /**
     * Position in the lifecycle. Both final states share the top rank, so neither can follow the other.
     */
    public function rank(): int
    {
        return match ($this) {
            self::Pending => 0,
            self::Queued => 1,
            self::Processing => 2,
            self::Succeeded, self::Failed => 3,
        };
    }

    public function isFinal(): bool
    {
        return $this === self::Succeeded || $this === self::Failed;
    }

    /**
     * True when moving from this status to $next is a real step forward.
     * Staying in the same status is not a transition, so it returns false.
     */
    public function canTransitionTo(self $next): bool
    {
        return ! $this->isFinal() && $next->rank() > $this->rank();
    }

    /**
     * The timestamp column stamped when a generation enters this status, if any.
     */
    public function lifecycleTimestampColumn(): ?string
    {
        return match ($this) {
            self::Pending => null,
            self::Queued => 'submitted_at',
            self::Processing => 'started_at',
            self::Succeeded, self::Failed => 'completed_at',
        };
    }

    /**
     * Statuses from which $this can be reached. Used as the guard in the conditional UPDATE.
     *
     * @return list<self>
     */
    public function allowedPredecessors(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $from): bool => $from->canTransitionTo($this),
        ));
    }
}

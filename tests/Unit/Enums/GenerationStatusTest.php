<?php

namespace Tests\Unit\Enums;

use App\Enums\GenerationStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test strategy: the lifecycle rules live in the enum, so every one of the 25 (from, to) pairs
 * is checked here without a database. Forward moves (including skips) are allowed; backward
 * moves, same-status moves, and anything out of a final state are not.
 */
class GenerationStatusTest extends TestCase
{
    /**
     * Every (from, to) pair with whether it is a real forward move.
     *
     * @return iterable<string, array{GenerationStatus, GenerationStatus, bool}>
     */
    public static function transitions(): iterable
    {
        $allowed = [
            'pending' => ['queued', 'processing', 'succeeded', 'failed'],
            'queued' => ['processing', 'succeeded', 'failed'],
            'processing' => ['succeeded', 'failed'],
            'succeeded' => [],
            'failed' => [],
        ];

        foreach (GenerationStatus::cases() as $from) {
            foreach (GenerationStatus::cases() as $to) {
                $isAllowed = in_array($to->value, $allowed[$from->value], true);

                yield "{$from->value} → {$to->value}" => [$from, $to, $isAllowed];
            }
        }
    }

    #[DataProvider('transitions')]
    public function test_it_only_allows_forward_transitions(GenerationStatus $from, GenerationStatus $to, bool $allowed): void
    {
        $this->assertSame($allowed, $from->canTransitionTo($to));
    }

    #[DataProvider('transitions')]
    public function test_allowed_predecessors_match_the_transition_rules(GenerationStatus $from, GenerationStatus $to, bool $allowed): void
    {
        $this->assertSame($allowed, in_array($from, $to->allowedPredecessors(), true));
    }

    public function test_only_succeeded_and_failed_are_final(): void
    {
        $final = array_filter(GenerationStatus::cases(), fn (GenerationStatus $s): bool => $s->isFinal());

        $this->assertSame([GenerationStatus::Succeeded, GenerationStatus::Failed], array_values($final));
    }
}

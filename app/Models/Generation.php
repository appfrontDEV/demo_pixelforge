<?php

namespace App\Models;

use App\Casts\AsCents;
use App\Casts\AsNanoUsd;
use App\Enums\GenerationStatus;
use App\Money\Cents;
use App\Money\NanoUsd;
use Database\Factories\GenerationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One image generation request and its outcome.
 *
 * `status` is not fillable: change it only through {@see Generation::transitionTo()}, which
 * enforces the forward-only lifecycle in {@see GenerationStatus}.
 *
 * @property string $id
 * @property int|null $user_id
 * @property string|null $provider_id
 * @property string $prompt
 * @property string $size
 * @property string $quality
 * @property list<string>|null $reference_images
 * @property GenerationStatus $status
 * @property Cents|null $estimate_min_cents
 * @property Cents|null $estimate_max_cents
 * @property array<string, mixed>|null $usage
 * @property array<string, mixed>|null $rates
 * @property NanoUsd|null $provider_cost_nusd
 * @property NanoUsd|null $price_nusd
 * @property string|null $image_url
 * @property Carbon|null $image_url_expires_at
 * @property string|null $error_code
 * @property string|null $error_message
 * @property Carbon|null $submitted_at
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 */
#[Fillable([
    'user_id',
    'provider_id',
    'prompt',
    'size',
    'quality',
    'reference_images',
    'estimate_min_cents',
    'estimate_max_cents',
    'usage',
    'rates',
    'provider_cost_nusd',
    'price_nusd',
    'image_url',
    'image_url_expires_at',
    'error_code',
    'error_message',
    'submitted_at',
    'started_at',
    'completed_at',
])]
class Generation extends Model
{
    /** @use HasFactory<GenerationFactory> */
    use HasFactory, HasUuids;

    /**
     * New rows start as pending, matching the column default.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GenerationStatus::class,
            'reference_images' => 'array',
            'usage' => 'array',
            'rates' => 'array',
            'estimate_min_cents' => AsCents::class,
            'estimate_max_cents' => AsCents::class,
            'provider_cost_nusd' => AsNanoUsd::class,
            'price_nusd' => AsNanoUsd::class,
            'image_url_expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Moves the generation forward to $next, atomically, together with $attributes.
     *
     * Input:
     *  - $next: the target status.
     *  - $attributes: extra fillable columns to write in the same UPDATE (e.g. cost and price on
     *    success). Casts apply, so pass Cents / NanoUsd / Carbon values as usual.
     * Output: true when the row moved. False, with nothing written, when the move is backwards,
     * leaves a final state, or targets the current status (repeat deliveries are no-ops).
     *
     * The guard is a conditional UPDATE on the stored status, so a stale in-memory copy (e.g. a
     * late webhook racing the reconcile job) can never move the row back. The lifecycle timestamp
     * for $next (submitted_at / started_at / completed_at) defaults to now() unless already set or
     * given in $attributes. When nothing moves, the model is refreshed to what is actually stored.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function transitionTo(GenerationStatus $next, array $attributes = []): bool
    {
        $changes = $this->storedChangesFor($next, $this->withLifecycleTimestamp($next, $attributes));

        $moved = static::query()
            ->whereKey($this->getKey())
            ->whereIn('status', array_column($next->allowedPredecessors(), 'value'))
            ->toBase()
            ->update($changes) === 1;

        $moved ? $this->syncStoredChanges($changes) : $this->refresh();

        return $moved;
    }

    /**
     * Input: the target status and the caller's attributes.
     * Output: the attributes plus now() for the status's lifecycle column, unless the caller
     * gave one or the model already has one.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function withLifecycleTimestamp(GenerationStatus $next, array $attributes): array
    {
        $column = $next->lifecycleTimestampColumn();

        if ($column !== null && ! array_key_exists($column, $attributes) && $this->getAttribute($column) === null) {
            $attributes[$column] = $this->freshTimestamp();
        }

        return $attributes;
    }

    /**
     * Input: the target status and the attributes to write.
     * Output: the raw column values (after mutators and casts) for those attributes, status and
     * updated_at. A clone is used so this model is untouched until the UPDATE succeeds.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function storedChangesFor(GenerationStatus $next, array $attributes): array
    {
        $draft = (clone $this)->fill($attributes);
        $draft->status = $next;
        $draft->updateTimestamps();

        return array_intersect_key(
            $draft->getAttributes(),
            array_flip([...array_keys($attributes), 'status', $this->getUpdatedAtColumn()]),
        );
    }

    /**
     * Input: raw column values just written by transitionTo(). Output: none; the model now
     * holds them as its clean, persisted state.
     *
     * @param  array<string, mixed>  $changes
     */
    private function syncStoredChanges(array $changes): void
    {
        $this->setRawAttributes(array_merge($this->getAttributes(), $changes));
        $this->syncOriginalAttributes(array_keys($changes));
    }
}

<?php

namespace App\Services\Pixelforge\Exceptions;

/**
 * 409 `idempotency_key_reused`: the Idempotency-Key was already used with different parameters.
 */
class IdempotencyKeyReusedException extends PixelforgeException {}

<?php

namespace App\Services\Pixelforge\Exceptions;

/**
 * An error after which the same request may be retried later (nothing was created or billed).
 * Wait at least `retryAfterSeconds` when present, and cap the number of attempts.
 */
abstract class RetryableException extends PixelforgeException {}

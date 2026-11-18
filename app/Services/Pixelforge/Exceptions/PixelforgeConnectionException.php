<?php

namespace App\Services\Pixelforge\Exceptions;

/**
 * No response was received (DNS, connection or timeout failure). Retry with the same Idempotency-Key.
 */
class PixelforgeConnectionException extends RetryableException {}

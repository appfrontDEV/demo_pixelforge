<?php

namespace App\Services\Pixelforge\Exceptions;

/**
 * 503 `overloaded`: Pixelforge is temporarily overloaded. Nothing was created; retry with the same Idempotency-Key.
 */
class OverloadedException extends RetryableException {}

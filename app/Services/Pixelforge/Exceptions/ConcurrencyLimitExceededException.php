<?php

namespace App\Services\Pixelforge\Exceptions;

/**
 * 429 `concurrency_limit_exceeded`: too many generations queued or processing. Retry once one finishes.
 */
class ConcurrencyLimitExceededException extends RetryableException {}

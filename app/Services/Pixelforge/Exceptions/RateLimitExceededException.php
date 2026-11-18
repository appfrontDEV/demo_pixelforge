<?php

namespace App\Services\Pixelforge\Exceptions;

/**
 * 429 `rate_limit_exceeded`: too many requests this minute. Retry after `retryAfterSeconds`.
 */
class RateLimitExceededException extends RetryableException {}

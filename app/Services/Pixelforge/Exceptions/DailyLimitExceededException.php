<?php

namespace App\Services\Pixelforge\Exceptions;

/**
 * 429 `daily_limit_exceeded`: the daily (UTC) generation quota is used up. Do not retry today.
 */
class DailyLimitExceededException extends PixelforgeException {}

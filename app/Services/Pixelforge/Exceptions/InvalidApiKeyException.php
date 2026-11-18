<?php

namespace App\Services\Pixelforge\Exceptions;

/**
 * 401 `invalid_api_key`: the API key is missing, malformed or revoked.
 */
class InvalidApiKeyException extends PixelforgeException {}

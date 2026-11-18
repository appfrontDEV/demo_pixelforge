<?php

namespace App\Services\Pixelforge;

use App\Services\Pixelforge\Exceptions\InvalidApiKeyException;
use App\Services\Pixelforge\Exceptions\PixelforgeException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * The only gateway to the Pixelforge API (https://pixelforge.promptedit.dev/docs).
 *
 * Returns decoded JSON arrays on success and throws a typed {@see PixelforgeException}
 * on any failure, so callers never inspect raw responses or status codes.
 */
class PixelforgeClient
{
    public const string PRICING_CACHE_KEY = 'pixelforge.pricing';

    /**
     * @param  string  $baseUrl  e.g. https://pixelforge.promptedit.dev
     * @param  ?string  $apiKey  one key per environment; required for authenticated calls only
     */
    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $apiKey,
        private readonly int $timeoutSeconds,
        private readonly int $pricingCacheSeconds,
        private readonly PixelforgeErrorFactory $errorFactory,
    ) {}

    /**
     * Current rates and token rules from GET /v1/pricing (no auth), cached as a plain array.
     * Failures are thrown and never cached.
     *
     * @return array{rates: array<string, string>, tokens: array<string, mixed>}
     */
    public function pricing(): array
    {
        return Cache::remember(self::PRICING_CACHE_KEY, $this->pricingCacheSeconds, function (): array {
            $pricing = $this->send($this->request(), 'GET', '/v1/pricing');

            if (! is_array($pricing['rates'] ?? null) || ! is_array($pricing['tokens'] ?? null)) {
                throw new PixelforgeException('Pixelforge returned an invalid pricing payload.', 'unexpected_response');
            }

            return ['rates' => $pricing['rates'], 'tokens' => $pricing['tokens']];
        });
    }

    /**
     * A generation as it is now, from GET /v1/generations/{id}.
     *
     * @param  string  $providerId  Pixelforge id, e.g. "gen_01JC2Z8Q…"
     * @return array<string, mixed>
     */
    public function generation(string $providerId): array
    {
        return $this->send($this->authenticatedRequest(), 'GET', '/v1/generations/'.rawurlencode($providerId));
    }

    /**
     * Base request: JSON in and out, with timeouts.
     */
    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->asJson()
            ->acceptJson()
            ->timeout($this->timeoutSeconds)
            ->connectTimeout($this->timeoutSeconds);
    }

    /**
     * Base request plus the bearer key. Refuses to build without a key so nothing unauthenticated is sent.
     */
    private function authenticatedRequest(): PendingRequest
    {
        if ($this->apiKey === null || $this->apiKey === '') {
            throw new InvalidApiKeyException('PIXELFORGE_API_KEY is not configured.', 'invalid_api_key');
        }

        return $this->request()->withToken($this->apiKey);
    }

    /**
     * Send a request and return the decoded JSON object, or throw the matching typed exception.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function send(PendingRequest $request, string $method, string $path, array $payload = []): array
    {
        try {
            $response = $request->send($method, $path, $payload === [] ? [] : ['json' => $payload]);
        } catch (ConnectionException $exception) {
            throw $this->errorFactory->fromConnectionError($exception);
        }

        if ($response->failed()) {
            throw $this->errorFactory->fromResponse($response);
        }

        $body = $response->json();

        return is_array($body) ? $body : throw $this->errorFactory->unexpectedResponse($response, 'Pixelforge returned a non-JSON body.');
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateApiToken;
use App\Actions\ListApiTokens;
use App\Actions\RevokeApiToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApiTokenIssueRequest;
use App\Http\Resources\ApiTokenResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * API tokens over /api/v1/tokens. Documented in docs/openapi.yaml.
 */
class ApiTokenController extends Controller
{
    /**
     * POST /api/v1/tokens: exchange credentials for a new token.
     * Output: 201 {data: {id, name, last_used_at, created_at, token}}; `token` is shown only here.
     */
    public function store(ApiTokenIssueRequest $request, CreateApiToken $createApiToken): JsonResponse
    {
        $newToken = $createApiToken->handle($request->authenticatedUser(), $request->string('name')->value());

        return (new ApiTokenResource($newToken->accessToken))
            ->additional(['data' => ['token' => $newToken->plainTextToken]])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /api/v1/tokens: the caller's tokens, newest first. Output: 200 {data: [...]}.
     */
    public function index(Request $request, ListApiTokens $listApiTokens): AnonymousResourceCollection
    {
        return ApiTokenResource::collection($listApiTokens->handle($request->user()));
    }

    /**
     * DELETE /api/v1/tokens/{token}: revoke one of the caller's tokens. Output: 204, or 404.
     */
    public function destroy(Request $request, int $token, RevokeApiToken $revokeApiToken): Response
    {
        $revokeApiToken->handle($request->user(), $token);

        return response()->noContent();
    }
}

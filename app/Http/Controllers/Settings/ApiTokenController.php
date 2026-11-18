<?php

namespace App\Http\Controllers\Settings;

use App\Actions\CreateApiToken;
use App\Actions\ListApiTokens;
use App\Actions\RevokeApiToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApiTokenStoreRequest;
use App\Http\Resources\ApiTokenResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The API tokens settings page. Same actions as the API and the console.
 */
class ApiTokenController extends Controller
{
    /**
     * Show the signed-in user's tokens (metadata only, never secrets).
     */
    public function index(Request $request, ListApiTokens $listApiTokens): Response
    {
        return Inertia::render('settings/ApiTokens', [
            'tokens' => ApiTokenResource::collection($listApiTokens->handle($request->user()))->resolve(),
        ]);
    }

    /**
     * Create a token and flash its plain-text value once as `newToken`.
     */
    public function store(ApiTokenStoreRequest $request, CreateApiToken $createApiToken): RedirectResponse
    {
        $newToken = $createApiToken->handle($request->user(), $request->string('name')->value());

        Inertia::flash('newToken', $newToken->plainTextToken);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('API token created.')]);

        return back();
    }

    /**
     * Revoke one of the signed-in user's tokens; 404 for anyone else's.
     */
    public function destroy(Request $request, int $token, RevokeApiToken $revokeApiToken): RedirectResponse
    {
        $revokeApiToken->handle($request->user(), $token);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('API token revoked.')]);

        return back();
    }
}

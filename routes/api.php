<?php

use App\Http\Controllers\Api\V1\ApiTokenController;
use App\Http\Controllers\Api\V1\CostEstimateController;
use Illuminate\Support\Facades\Route;

/*
 * Versioned, token-only JSON API. Every route here is documented in docs/openapi.yaml.
 */
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('tokens', [ApiTokenController::class, 'store'])
        ->middleware(['throttle:6,1', 'throttle:login'])
        ->name('tokens.store');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('tokens', [ApiTokenController::class, 'index'])->name('tokens.index');
        Route::delete('tokens/{token}', [ApiTokenController::class, 'destroy'])
            ->whereNumber('token')
            ->name('tokens.destroy');
        Route::get('cost-estimate', CostEstimateController::class)->name('cost-estimate');
    });
});

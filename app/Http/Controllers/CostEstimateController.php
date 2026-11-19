<?php

namespace App\Http\Controllers;

use App\Actions\EstimateCost;
use App\Http\Requests\CostEstimateRequest;
use App\Http\Resources\CostEstimateResource;

/**
 * GET /cost-estimate (session auth): fetched by the prompt form while the user picks options.
 */
class CostEstimateController extends Controller
{
    /**
     * Input: size and quality in the query string.
     * Output: 200 {data: {size, quality, min_cents, max_cents, sample_count}}, or 422.
     */
    public function __invoke(CostEstimateRequest $request, EstimateCost $estimateCost): CostEstimateResource
    {
        return new CostEstimateResource($estimateCost->handle($request->size(), $request->quality()));
    }
}

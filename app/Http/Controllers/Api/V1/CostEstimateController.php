<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\EstimateCost;
use App\Http\Controllers\Controller;
use App\Http\Requests\CostEstimateRequest;
use App\Http\Resources\CostEstimateResource;

/**
 * GET /api/v1/cost-estimate (token auth). Documented in docs/openapi.yaml.
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

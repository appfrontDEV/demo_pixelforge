<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateGeneration;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGenerationRequest;
use App\Http\Resources\GenerationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Generations over /api/v1/generations. Documented in docs/openapi.yaml.
 */
class GenerationController extends Controller
{
    /**
     * POST /api/v1/generations: store a pending generation and queue its submission.
     * Output: 202 {data: generation} (status `pending`), or 422 on the invalid field.
     */
    public function store(StoreGenerationRequest $request, CreateGeneration $createGeneration): JsonResponse
    {
        $generation = $createGeneration->handle(
            $request->user(),
            $request->string('prompt')->value(),
            $request->size(),
            $request->quality(),
            $request->referenceImages(),
        );

        return (new GenerationResource($generation))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }
}

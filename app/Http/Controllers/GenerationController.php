<?php

namespace App\Http\Controllers;

use App\Actions\CreateGeneration;
use App\Http\Requests\StoreGenerationRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Generations on the web (session auth). Same action as the API and the console; the prompt
 * form that posts here arrives with #10.
 */
class GenerationController extends Controller
{
    /**
     * POST /generations: store a pending generation and queue its submission.
     * Output: back with a success toast, or back with errors on the invalid field.
     */
    public function store(StoreGenerationRequest $request, CreateGeneration $createGeneration): RedirectResponse
    {
        $createGeneration->handle(
            $request->user(),
            $request->string('prompt')->value(),
            $request->size(),
            $request->quality(),
            $request->referenceImages(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Generation queued.')]);

        return back();
    }
}

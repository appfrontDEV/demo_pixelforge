<?php

namespace App\Console\Commands;

use App\Actions\CreateGeneration;
use App\Concerns\GenerationValidationRules;
use App\Enums\ImageQuality;
use App\Enums\ImageSize;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('generations:create
    {email : Email of the owner}
    {prompt : What to generate (at most 4000 characters)}
    {--size= : 1024x1024 (default), 1536x1024 or 1024x1536}
    {--quality= : low, medium (default) or high}
    {--reference-image=* : An http(s) image URL to guide the style; repeat up to 4 times}')]
#[Description('Create a generation for a user and queue its submission to Pixelforge')]
class GenerationCreate extends Command
{
    use GenerationValidationRules;

    /**
     * Input: owner email, prompt and options (same rules as the web and the API).
     * Output: the new generation's id and estimate; exit 1 for an unknown email or invalid input.
     */
    public function handle(CreateGeneration $createGeneration): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();
        $validator = Validator::make($this->validationInput(), $this->generationRules(), $this->generationMessages());

        if ($user === null || $validator->fails()) {
            $this->components->error($user === null ? 'No user with that email.' : $validator->errors()->first());

            return self::FAILURE;
        }

        $generation = $this->createFrom($createGeneration, $user, $validator->validated());

        $this->components->info("Generation queued: {$generation->id}");
        $this->line("Estimate: {$generation->estimate_min_cents?->format()} – {$generation->estimate_max_cents?->format()}");

        return self::SUCCESS;
    }

    /**
     * Input: the owner and the validated input. Output: the new generation, with the provider
     * defaults for a missing size or quality.
     *
     * @param  array<string, mixed>  $input
     */
    private function createFrom(CreateGeneration $createGeneration, User $user, array $input): Generation
    {
        return $createGeneration->handle(
            $user,
            $input['prompt'],
            ImageSize::tryFrom($input['size'] ?? '') ?? ImageSize::default(),
            ImageQuality::tryFrom($input['quality'] ?? '') ?? ImageQuality::default(),
            array_values($input['reference_images'] ?? []),
        );
    }

    /**
     * Output: the arguments and options in the shape the shared rules expect.
     *
     * @return array<string, mixed>
     */
    private function validationInput(): array
    {
        return [
            'prompt' => $this->argument('prompt'),
            'size' => $this->option('size'),
            'quality' => $this->option('quality'),
            'reference_images' => $this->option('reference-image'),
        ];
    }
}

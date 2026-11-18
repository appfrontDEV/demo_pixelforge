<?php

namespace App\Providers;

use App\Services\Pixelforge\PixelforgeClient;
use App\Services\Pixelforge\PixelforgeErrorFactory;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PixelforgeClient::class, fn (Application $app): PixelforgeClient => new PixelforgeClient(
            baseUrl: (string) config('services.pixelforge.base_url'),
            apiKey: config('services.pixelforge.api_key'),
            timeoutSeconds: (int) config('services.pixelforge.timeout'),
            pricingCacheSeconds: (int) config('services.pixelforge.pricing_cache_seconds'),
            errorFactory: $app->make(PixelforgeErrorFactory::class),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

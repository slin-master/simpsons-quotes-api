<?php

namespace App\Providers;

use App\Config\SimpsonsApiConfig;
use App\Contracts\QuoteProvider;
use App\Services\Quotes\MockQuoteProvider;
use App\Services\Quotes\ResilientQuoteProvider;
use App\Services\Quotes\TheSimpsonsApiQuoteProvider;
use App\Services\Quotes\UserQuoteService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /** @var array<string, mixed> $simpsonsApiConfig */
        $simpsonsApiConfig = config('simpsons.api', []);

        $this->app->singleton(SimpsonsApiConfig::class, fn () => SimpsonsApiConfig::fromArray($simpsonsApiConfig));

        $this->app->singleton(MockQuoteProvider::class, fn () => new MockQuoteProvider(
            resource_path('data/mock-simpsons-quotes.json')
        ));

        $this->app->singleton(TheSimpsonsApiQuoteProvider::class, function ($app) {
            /** @var SimpsonsApiConfig $config */
            $config = $app->make(SimpsonsApiConfig::class);

            return new TheSimpsonsApiQuoteProvider(
                baseUrl: $config->baseUrl,
                cdnBaseUrl: $config->cdnBaseUrl,
                imageSize: $config->imageSize,
                pageMin: $config->pageMin,
                pageMax: $config->pageMax,
                timeoutSeconds: $config->timeoutSeconds,
                retryAttempts: $config->retryAttempts,
            );
        });

        $this->app->singleton(QuoteProvider::class, function ($app) {
            return new ResilientQuoteProvider(
                primary: $app->make(TheSimpsonsApiQuoteProvider::class),
                fallback: $app->make(MockQuoteProvider::class),
            );
        });

        $this->app->singleton(UserQuoteService::class, function ($app) {
            return new UserQuoteService(
                quoteProvider: $app->make(QuoteProvider::class),
                quotesPerUser: $this->configInt(config('simpsons.quotes_per_user'), 5),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();
    }

    private function configInt(mixed $value, int $default): int
    {
        return is_int($value) ? $value : $default;
    }
}

<?php

namespace Tests\Unit\Providers;

use App\Config\SimpsonsApiConfig;
use App\Contracts\QuoteProvider;
use App\Http\Resources\QuoteResource;
use App\Providers\AppServiceProvider;
use App\Services\Quotes\MockQuoteProvider;
use App\Services\Quotes\ResilientQuoteProvider;
use App\Services\Quotes\TheSimpsonsApiQuoteProvider;
use App\Services\Quotes\UserQuoteService;
use Illuminate\Http\Resources\Json\JsonResource;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_it_registers_quote_related_singletons_from_configuration(): void
    {
        config()->set('simpsons.api', [
            'base_url' => 'https://example.test/api',
            'cdn_base_url' => 'https://cdn.example.test',
            'image_size' => 200,
            'page_min' => 2,
            'page_max' => 4,
            'timeout_seconds' => 9,
            'retry_attempts' => 3,
        ]);
        config()->set('simpsons.quotes_per_user', 4);

        $this->app->forgetInstance(SimpsonsApiConfig::class);
        $this->app->forgetInstance(MockQuoteProvider::class);
        $this->app->forgetInstance(TheSimpsonsApiQuoteProvider::class);
        $this->app->forgetInstance(QuoteProvider::class);
        $this->app->forgetInstance(UserQuoteService::class);

        (new AppServiceProvider($this->app))->register();

        $config = app(SimpsonsApiConfig::class);
        $provider = app(QuoteProvider::class);

        $this->assertInstanceOf(SimpsonsApiConfig::class, $config);
        $this->assertSame('https://example.test/api', $config->baseUrl);
        $this->assertSame('https://cdn.example.test', $config->cdnBaseUrl);
        $this->assertSame(200, $config->imageSize);
        $this->assertSame(2, $config->pageMin);
        $this->assertSame(4, $config->pageMax);
        $this->assertSame(9, $config->timeoutSeconds);
        $this->assertSame(3, $config->retryAttempts);

        $this->assertInstanceOf(MockQuoteProvider::class, app(MockQuoteProvider::class));
        $this->assertInstanceOf(TheSimpsonsApiQuoteProvider::class, app(TheSimpsonsApiQuoteProvider::class));
        $this->assertInstanceOf(ResilientQuoteProvider::class, $provider);
        $this->assertInstanceOf(UserQuoteService::class, app(UserQuoteService::class));
    }

    public function test_it_disables_resource_wrapping(): void
    {
        JsonResource::wrap('data');

        (new AppServiceProvider($this->app))->boot();

        $resource = new QuoteResource((object) [
            'id' => 1,
            'quote' => 'Test quote',
            'character' => 'Test character',
            'image_url' => null,
            'source' => 'fake',
            'fetched_at' => null,
        ]);

        $responseData = $resource->response()->getData(true);

        $this->assertIsArray($responseData);
        $this->assertArrayNotHasKey('data', $responseData);
    }
}

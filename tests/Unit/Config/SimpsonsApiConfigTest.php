<?php

namespace Tests\Unit\Config;

use App\Config\SimpsonsApiConfig;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SimpsonsApiConfigTest extends TestCase
{
    #[Test]
    public function it_builds_a_config_object_from_array_values(): void
    {
        $config = SimpsonsApiConfig::fromArray([
            'base_url' => 'https://example.test/api',
            'cdn_base_url' => 'https://cdn.example.test',
            'image_size' => 200,
            'page_min' => 2,
            'page_max' => 12,
            'timeout_seconds' => 3,
            'retry_attempts' => 1,
        ]);

        $this->assertSame('https://example.test/api', $config->baseUrl);
        $this->assertSame('https://cdn.example.test', $config->cdnBaseUrl);
        $this->assertSame(200, $config->imageSize);
        $this->assertSame(2, $config->pageMin);
        $this->assertSame(12, $config->pageMax);
        $this->assertSame(3, $config->timeoutSeconds);
        $this->assertSame(1, $config->retryAttempts);
    }

    #[Test]
    public function it_falls_back_to_defaults_for_invalid_values(): void
    {
        $config = SimpsonsApiConfig::fromArray([
            'base_url' => '',
            'cdn_base_url' => null,
            'image_size' => '500',
            'page_min' => '1',
            'page_max' => null,
            'timeout_seconds' => false,
            'retry_attempts' => [],
        ]);

        $this->assertSame('https://thesimpsonsapi.com/api', $config->baseUrl);
        $this->assertSame('https://cdn.thesimpsonsapi.com', $config->cdnBaseUrl);
        $this->assertSame(500, $config->imageSize);
        $this->assertSame(1, $config->pageMin);
        $this->assertSame(60, $config->pageMax);
        $this->assertSame(5, $config->timeoutSeconds);
        $this->assertSame(2, $config->retryAttempts);
    }
}

<?php

namespace App\Config;

final readonly class SimpsonsApiConfig
{
    public function __construct(
        public string $baseUrl,
        public string $cdnBaseUrl,
        public int $imageSize,
        public int $pageMin,
        public int $pageMax,
        public int $timeoutSeconds,
        public int $retryAttempts,
    ) {
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            baseUrl: self::stringValue($config['base_url'] ?? null, 'https://thesimpsonsapi.com/api'),
            cdnBaseUrl: self::stringValue($config['cdn_base_url'] ?? null, 'https://cdn.thesimpsonsapi.com'),
            imageSize: self::intValue($config['image_size'] ?? null, 500),
            pageMin: self::intValue($config['page_min'] ?? null, 1),
            pageMax: self::intValue($config['page_max'] ?? null, 60),
            timeoutSeconds: self::intValue($config['timeout_seconds'] ?? null, 5),
            retryAttempts: self::intValue($config['retry_attempts'] ?? null, 2),
        );
    }

    private static function stringValue(mixed $value, string $default): string
    {
        return is_string($value) && $value !== '' ? $value : $default;
    }

    private static function intValue(mixed $value, int $default): int
    {
        return is_int($value) ? $value : $default;
    }
}

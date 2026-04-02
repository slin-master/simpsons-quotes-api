<?php

return [
    'api' => [
        'base_url' => env('SIMPSONS_API_BASE_URL', 'https://thesimpsonsapi.com/api'),
        'cdn_base_url' => env('SIMPSONS_API_CDN_BASE_URL', 'https://cdn.thesimpsonsapi.com'),
        'image_size' => (int) env('SIMPSONS_API_IMAGE_SIZE', 500),
        'page_min' => (int) env('SIMPSONS_API_PAGE_MIN', 1),
        'page_max' => (int) env('SIMPSONS_API_PAGE_MAX', 60),
        'timeout_seconds' => (int) env('SIMPSONS_API_TIMEOUT_SECONDS', 5),
        'retry_attempts' => (int) env('SIMPSONS_API_RETRY_ATTEMPTS', 2),
    ],
    'quotes_per_user' => (int) env('SIMPSONS_QUOTES_PER_USER', 5),
];

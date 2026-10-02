<?php

declare(strict_types=1);

return [
    /*
     * Default provider identifier
     */
    'default' => env('COMIC_PROVIDER', 'komiku'),

    /*
     * Komiku upstream provider configuration
     */
    'komiku' => [
        'base_url' => env('KOMIKU_API_BASE_URL', 'https://komik-api-wine.vercel.app'),
        'connect_timeout' => (int) env('KOMIKU_CONNECT_TIMEOUT', 3),
        'timeout' => (int) env('KOMIKU_TIMEOUT', 8),
        'max_retries' => (int) env('KOMIKU_MAX_RETRIES', 2),
        'retry_delay_ms' => (int) env('KOMIKU_RETRY_DELAY_MS', 100),
    ],

    /*
     * Allowed image hosts for cover thumbnails and chapter pages.
     * Prevents SSRF and hotlinking to untrusted/internal domains.
     */
    'image_allowed_hosts' => array_filter(array_map('trim', explode(',', env('KOMIKU_IMAGE_ALLOWED_HOSTS', '')))) ?: [
        'komiku.id',
        'komiku.org',
        'komiku.to',
        'komiku-rest-api.vercel.app',
        'komik-api-wine.vercel.app',
        'img.komiku.id',
        'img.komiku.org',
        'thumbnail.komiku.org',
        'cdn.komiku.id',
        'i0.wp.com',
        'i1.wp.com',
        'i2.wp.com',
        'i3.wp.com',
    ],

    /*
     * Cache TTL in seconds (Fresh TTL and Stale fallback TTL)
     */
    'cache' => [
        'prefix' => 'v1:comic',
        'ttl' => [
            'genre' => [
                'fresh' => 86400,       // 24 hours
                'stale' => 604800,      // 7 days
            ],
            'latest' => [
                'fresh' => 300,         // 5 minutes
                'stale' => 3600,        // 1 hour
            ],
            'popular' => [
                'fresh' => 900,         // 15 minutes
                'stale' => 21600,       // 6 hours
            ],
            'recommended' => [
                'fresh' => 900,         // 15 minutes
                'stale' => 21600,       // 6 hours
            ],
            'search' => [
                'fresh' => 600,         // 10 minutes
                'stale' => 3600,        // 1 hour
            ],
            'detail' => [
                'fresh' => 1800,        // 30 minutes
                'stale' => 86400,       // 24 hours
            ],
            'chapter' => [
                'fresh' => 21600,       // 6 hours
                'stale' => 604800,      // 7 days
            ],
            'ranking' => [
                'fresh' => 900,         // 15 minutes
                'stale' => 7200,        // 2 hours
            ],
        ],
    ],

    /*
     * View tracking and qualified deduplication configuration
     */
    'views' => [
        'deduplication_hours' => (int) env('VIEW_DEDUPLICATION_HOURS', 6),
        'retention_days' => (int) env('RAW_VIEW_RETENTION_DAYS', 90),
    ],

    /*
     * Internal popularity ranking configuration
     */
    'ranking' => [
        'limit' => (int) env('RANKING_LIMIT', 50),
    ],
];

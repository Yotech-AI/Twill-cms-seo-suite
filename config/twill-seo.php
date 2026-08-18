<?php

return [

    // Master switch. When false the package only registers its manifest on
    // the shared Plugins page so the card explains why nothing else is live.
    'enabled' => env('TWILL_SEO_ENABLED', true),

    /*
     * Twill models managed by the SEO suite, keyed by a stable registry key.
     * Keys are what the admin panel and analyze endpoint exchange — never
     * expose or accept class names from the client.
     *
     * 'articles' => [
     *     'model' => App\Models\Article::class,
     *     'title_attribute' => 'title',
     *     'schema_type' => 'Article',
     *     'sitemap' => true,
     *     'sitemap_images' => false,
     *     'image_role' => null,
     *     'url' => null,
     *     'content' => null,
     *     'content_fields' => [],
     *     'breadcrumbs' => null,
     * ],
     */
    'models' => [],

    // Config-level defaults; the admin settings row overrides these at runtime.
    'features' => [
        'analysis' => true,
        'sitemap' => true,
        'schema' => true,
        'og' => true,
        'twitter' => true,
        'hreflang' => false,
    ],

    'title' => [
        'default_template' => '{title} {sep} {site_name}',
        'separator' => '-',
    ],

    'robots' => [
        'default_directives' => ['max-snippet:-1', 'max-image-preview:large', 'max-video-preview:-1'],
    ],

    'sitemap' => [
        'path' => 'sitemap.xml',
        'per_page' => 1000,
        'cache_ttl' => 3600,
    ],

    'schema' => [
        'pieces' => [],
    ],

    'analysis' => [
        'refresh_scores_on_save' => true,
        'debounce_ms' => 500,
        'throttle' => '60,1',
    ],
];

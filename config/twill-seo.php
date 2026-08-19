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

    // Config-level defaults for SeoSettings' "general" group (site identity,
    // schema.org entity, default social share image) — the admin settings
    // row (twill_seo_settings.general) overrides these at runtime, same
    // pattern as 'features' above. site_name itself has no key here: it
    // falls back to config('app.name') directly, since Laravel already owns
    // that value.
    'general' => [
        'tagline' => '',
        'entity_type' => 'organization', // 'organization' | 'person'
        'entity_name' => null, // falls back to the resolved site name
        'logo_media_id' => null,
        'default_share_media_id' => null,
        'social_profiles' => [],
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
        'search_action_enabled' => false,
        // Literal {search_term_string} placeholder, e.g. '/search?q={search_term_string}'.
        'search_url_template' => null,
    ],

    'analysis' => [
        'refresh_scores_on_save' => true,
        'debounce_ms' => 500,
        'throttle' => '60,1',
    ],

    // Throttle for the settings admin's mutating endpoint (PUT /settings).
    // Looser than analysis.throttle above: this is a handful of deliberate
    // admin saves, not a per-keystroke debounced call.
    'settings' => [
        'throttle' => '30,1',
    ],
];

<?php

use A17\Twill\Models\Media;
use TwillSeo\Tests\Fixtures\Models\BrokenTranslatedAttributesArticle;
use TwillSeo\Tests\Fixtures\Models\UninitializedTranslatedAttributesArticle;

it('passes on the healthy fixture setup (articles + pages, both fully wired)', function () {
    $this->artisan('twill-seo:doctor')->assertExitCode(0);
});

it('reports the plugin registry, config, database tables and registered models as healthy', function () {
    $this->artisan('twill-seo:doctor')
        ->expectsOutputToContain('Plugins-page registry')
        ->expectsOutputToContain('Manifest registered')
        ->expectsOutputToContain('Database tables')
        ->expectsOutputToContain('articles, pages')
        ->assertExitCode(0);
});

it('warns rather than fails when twill-seo.models is empty', function () {
    config(['twill-seo.models' => []]);

    $this->artisan('twill-seo:doctor')
        ->expectsOutputToContain('twill-seo.models is empty')
        ->assertExitCode(0);
});

it('fails with a clear line when a registered model class does not exist', function () {
    config(['twill-seo.models' => [
        'broken' => [
            'model' => 'TwillSeo\\Tests\\Fixtures\\Models\\DoesNotExist',
            'title_attribute' => 'title',
        ],
    ]]);

    $this->artisan('twill-seo:doctor')
        ->expectsOutputToContain('TwillSeo\Tests\Fixtures\Models\DoesNotExist does not exist')
        ->assertExitCode(1);
});

it('fails with a clear line when a registered model does not use HasSeo', function () {
    // A17\Twill\Models\Media: a real Twill model, guaranteed to exist and to
    // NOT compose HasSeo — TwillSeo\Tests\Fixtures\Models\PlainModel would be
    // the wrong choice here despite its name, since it exists specifically to
    // prove HasSeo works on a bare Eloquent model and therefore uses it.
    config(['twill-seo.models' => [
        'broken' => [
            'model' => Media::class,
            'title_attribute' => 'filename',
        ],
    ]]);

    $this->artisan('twill-seo:doctor')
        ->expectsOutputToContain('does not use TwillSeo\Models\Behaviors\HasSeo')
        ->assertExitCode(1);
});

it('fails with a clear line when a translatedAttributes entry collides with a seo_* field name', function () {
    config(['twill-seo.models' => [
        'broken' => [
            'model' => BrokenTranslatedAttributesArticle::class,
            'title_attribute' => 'title',
        ],
    ]]);

    $this->artisan('twill-seo:doctor')
        ->expectsOutputToContain('translatedAttributes collides with a reserved seo_* field name: seo_title')
        ->assertExitCode(1);
});

it('warns rather than crashes when translatedAttributes is a typed property that was never initialized', function () {
    // A merely protected/private (untyped) declaration does NOT hit this
    // path at all — Eloquent's own __get() intercepts that read and
    // returns null, exactly like BrokenTranslatedAttributesArticle's own
    // healthy sibling checks would see for a model with no collision. Only
    // a typed, defaultless declaration throws on read (verified
    // empirically), which is what this fixture pins.
    config(['twill-seo.models' => [
        'broken' => [
            'model' => UninitializedTranslatedAttributesArticle::class,
            'title_attribute' => 'title',
        ],
    ]]);

    $this->artisan('twill-seo:doctor')
        ->expectsOutputToContain('Could not read translatedAttributes')
        ->assertExitCode(0);
});

it('warns when a registered model has no rows yet, rather than failing', function () {
    $this->artisan('twill-seo:doctor')
        ->expectsOutputToContain('no rows yet')
        ->assertExitCode(0);
});

it('warns when the settings site name is entirely empty', function () {
    config(['app.name' => '']);

    $this->artisan('twill-seo:doctor')
        ->expectsOutputToContain('No site name is configured')
        ->assertExitCode(0);
});

it('warns when hreflang is enabled with fewer than two locales', function () {
    config(['twill-seo.features.hreflang' => true, 'translatable.locales' => ['en']]);

    $this->artisan('twill-seo:doctor')
        ->expectsOutputToContain('Hreflang is enabled with only 1 locale')
        ->assertExitCode(0);
});

it('checks the sitemap index renders 200 when the sitemap feature is on', function () {
    $this->artisan('twill-seo:doctor')
        ->expectsOutputToContain('Sitemap index responded 200')
        ->assertExitCode(0);
});

it('reports the sitemap check as skipped when the feature is off', function () {
    config(['twill-seo.features.sitemap' => false]);

    $this->artisan('twill-seo:doctor')
        ->expectsOutputToContain('Sitemap feature is disabled')
        ->assertExitCode(0);
});

it('confirms both built dist assets are present', function () {
    $this->artisan('twill-seo:doctor')
        ->expectsOutputToContain('Built assets')
        ->assertExitCode(0);
});

it('runs the engine smoke test without throwing', function () {
    $this->artisan('twill-seo:doctor')
        ->expectsOutputToContain('Engine smoke test')
        ->assertExitCode(0);
});

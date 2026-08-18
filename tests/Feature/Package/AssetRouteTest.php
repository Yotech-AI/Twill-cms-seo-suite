<?php

use Illuminate\Support\ServiceProvider;
use TwillSeo\Http\Controllers\AssetController;
use TwillSeo\TwillSeoServiceProvider;

it('serves the built iife bundle with the right content type', function () {
    $this->get(twillSeoUrl('asset/twill-seo.iife.js'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/javascript; charset=utf-8')
        // Symfony's ResponseHeaderBag recomputes Cache-Control from its own
        // parsed directive set rather than passing the literal string
        // through, which re-serializes the directives in alphabetical
        // order — the values below are what BinaryFileResponse actually
        // sends on the wire, not a typo.
        ->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');
});

it('serves the built css with the right content type', function () {
    $this->get(twillSeoUrl('asset/twill-seo.css'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/css; charset=utf-8')
        // Symfony's ResponseHeaderBag recomputes Cache-Control from its own
        // parsed directive set rather than passing the literal string
        // through, which re-serializes the directives in alphabetical
        // order — the values below are what BinaryFileResponse actually
        // sends on the wire, not a typo.
        ->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');
});

it('sets an etag on the served asset', function () {
    $response = $this->get(twillSeoUrl('asset/twill-seo.iife.js'))->assertOk();

    expect($response->headers->get('ETag'))->not->toBeNull();
});

it('serves the asset with no authentication required, unlike the rest of the package', function () {
    // A separate web-only route group (no twill_auth) by design — a
    // <script src>/<link> tag fetches assets the same way a guest browser
    // would, and every other admin page needs this JS to render at all, so
    // it cannot itself sit behind the auth it would otherwise need to load.
    $this->get(twillSeoUrl('asset/twill-seo.iife.js'))->assertOk();
});

it('returns 304 not modified on a conditional request carrying the etag back', function () {
    $etag = $this->get(twillSeoUrl('asset/twill-seo.iife.js'))->assertOk()->headers->get('ETag');

    expect($etag)->not->toBeNull();

    $this->get(twillSeoUrl('asset/twill-seo.iife.js'), ['If-None-Match' => $etag])
        ->assertStatus(304);
});

it('404s on a file name outside the allow-listed pattern', function () {
    $this->get(twillSeoUrl('asset/not-a-real-file.js'))->assertNotFound();
    $this->get(twillSeoUrl('asset/twill-seo.iife.js.map'))->assertNotFound();
    $this->get(twillSeoUrl('asset/twill-seo.css.map'))->assertNotFound();
});

it('builds a route url carrying a version query string', function () {
    $url = AssetController::url('twill-seo.iife.js');

    // route() builds an absolute URL, so this checks containment rather
    // than a prefix against twillSeoUrl()'s bare path.
    expect($url)->toContain(twillSeoUrl('asset/twill-seo.iife.js'))
        ->and($url)->toMatch('/[?&]v=[^&]+/');
});

it('builds a url for the css file too', function () {
    $url = AssetController::url('twill-seo.css');

    expect($url)->toContain(twillSeoUrl('asset/twill-seo.css'));
});

it('prefers a published copy under public/vendor/twill-seo when a host has published it', function () {
    $publishedDir = public_path('vendor/twill-seo');
    @mkdir($publishedDir, 0777, true);
    file_put_contents($publishedDir.'/twill-seo.iife.js', '/* published copy */');

    try {
        $url = AssetController::url('twill-seo.iife.js');

        expect($url)->toContain('vendor/twill-seo/twill-seo.iife.js')
            ->and($url)->not->toContain('/seo/asset/');
    } finally {
        @unlink($publishedDir.'/twill-seo.iife.js');
        @rmdir($publishedDir);
    }
});

it('registers the twill-seo-assets publish tag from resources/dist to public/vendor/twill-seo', function () {
    $paths = ServiceProvider::pathsToPublish(
        TwillSeoServiceProvider::class,
        'twill-seo-assets'
    );

    expect($paths)->toHaveCount(1)
        ->and(array_values($paths)[0])->toBe(public_path('vendor/twill-seo'));
});

<?php

use Illuminate\Support\Facades\File;
use TwillSeo\TwillSeoServiceProvider;

/*
 * A physical public/sitemap.xml (e.g. a leftover spatie/laravel-sitemap
 * export) shadows the suite's route at the web-server level, where no
 * feature toggle can reach it. The provider deletes it at boot — but only
 * while the sitemap feature is actually on.
 */

function staticSitemapPath(): string
{
    return public_path(config('twill-seo.sitemap.path', 'sitemap.xml'));
}

function invokeStaticSitemapTakeover(): void
{
    $provider = app()->getProvider(TwillSeoServiceProvider::class);
    (new ReflectionMethod($provider, 'removeShadowingStaticSitemap'))->invoke($provider);
}

afterEach(fn () => File::delete(staticSitemapPath()));

it('deletes a static sitemap file shadowing the route when the feature is on', function () {
    config()->set('twill-seo.features.sitemap', true);
    File::put(staticSitemapPath(), '<urlset/>');

    invokeStaticSitemapTakeover();

    expect(is_file(staticSitemapPath()))->toBeFalse();
});

it('leaves the static sitemap alone while the feature is off', function () {
    config()->set('twill-seo.features.sitemap', false);
    File::put(staticSitemapPath(), '<urlset/>');

    invokeStaticSitemapTakeover();

    expect(is_file(staticSitemapPath()))->toBeTrue();
});

it('is a no-op when no static file exists', function () {
    config()->set('twill-seo.features.sitemap', true);

    invokeStaticSitemapTakeover();

    expect(is_file(staticSitemapPath()))->toBeFalse();
});

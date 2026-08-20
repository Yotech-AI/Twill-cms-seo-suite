<?php

use TwillSeo\Support\TranslatorMessageRenderer;

/*
 * Regression for the first Dutch host install: with app.locale AND
 * app.fallback_locale both 'nl', Laravel's translator echoed the raw
 * "twill-seo::analysis…" keys into the editor panel whenever the requested
 * line was missing in nl — the renderer must always end at a real sentence.
 */

it('renders Dutch feedback when the admin locale is nl, even with a nl fallback', function () {
    app()->setLocale('nl');
    config(['app.fallback_locale' => 'nl']);

    $text = app(TranslatorMessageRenderer::class)
        ->render('twill-seo::analysis.images.none', []);

    expect($text)->not->toContain('twill-seo::')
        ->and($text)->toContain('afbeelding');
});

it('replaces placeholders in the Dutch lines', function () {
    app()->setLocale('nl');

    $text = app(TranslatorMessageRenderer::class)
        ->render('twill-seo::analysis.keyword_density.good', ['count' => 6, 'density' => 1.5]);

    expect($text)->toContain('6')
        ->and($text)->toContain('1.5')
        ->and($text)->not->toContain(':count');
});

it('falls back to the shipped English line for a locale with no translation file at all', function () {
    // Worst case on purpose: the fallback locale ALSO has no file, which is
    // exactly the configuration that used to leak raw keys.
    app()->setLocale('de');
    config(['app.fallback_locale' => 'de']);

    $text = app(TranslatorMessageRenderer::class)
        ->render('twill-seo::analysis.images.none', []);

    expect($text)->not->toContain('twill-seo::')
        ->and($text)->toContain('image');
});

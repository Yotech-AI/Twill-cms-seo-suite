<?php

use A17\Twill\Facades\TwillNavigation;
use TwillSeo\TwillSeoServiceProvider;

/*
 * The tree, not the $links property, is what a user sees — and it applies
 * each link's shouldShow(), which requires an authenticated Twill admin.
 * Without one the tree is empty and every "does not contain" assertion
 * would pass vacuously; asserting Plugins is present guards against that.
 */

function twillSeoNavigationTitles(): array
{
    return collect(TwillNavigation::buildNavigationTree())
        ->flatten()
        ->map(fn ($link) => $link->getTitle())
        ->all();
}

beforeEach(fn () => test()->actingAsTwillAdmin());

it('adds no entry to the admin navigation by default', function () {
    $titles = twillSeoNavigationTitles();

    expect($titles)->toContain('Addons')
        ->and(config('twill-seo.ui.navigation_link'))->toBeFalse()
        ->and($titles)->not->toContain('SEO')
        // The settings singleton must never surface here either — its
        // capsule registers with automatic navigation switched off; the
        // Addons page is the package's home.
        ->and($titles)->not->toContain('SeoSettings');
});

it('adds a navigation entry when a host opts in', function () {
    $before = count(twillSeoNavigationTitles());

    config()->set('twill-seo.ui.navigation_link', true);

    // Re-invoke registration with the flag flipped: this proves the flag
    // GATES registration rather than merely existing in config.
    $provider = app()->getProvider(TwillSeoServiceProvider::class);
    $method = new ReflectionMethod($provider, 'registerNavigation');
    $method->invoke($provider);

    $titles = twillSeoNavigationTitles();

    expect(count($titles))->toBe($before + 1)
        ->and($titles)->toContain('SEO');
});

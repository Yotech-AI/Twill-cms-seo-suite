<?php

use TwillSeo\PluginPage\TwillPluginServiceProvider;
use TwillSeo\TwillSeoServiceProvider;

it('registers our manifest on the shared Plugins registry', function () {
    $registry = app(TwillPluginServiceProvider::REGISTRY_BINDING);

    expect($registry)->toHaveKey('yotech-ai/twill-cms-seo-suite');

    $manifest = $registry['yotech-ai/twill-cms-seo-suite'];

    expect($manifest['name'])->toBe('Twill SEO')
        ->and($manifest['route'])->toBe(config('twill.admin_route_name_prefix', 'twill.').'seoSetting');
});

it('owns the shared Plugins page — the only Yotech plugin installed here', function () {
    expect(app(TwillPluginServiceProvider::PAGE_OWNER_BINDING))->toBe(TwillSeoServiceProvider::class);
});

it('lists Twill SEO on the shared Plugins admin page', function () {
    $this->actingAsTwillAdmin()
        ->get(twillAdmin().'/plugins')
        ->assertOk()
        ->assertSee('Twill SEO');
});

/**
 * Regression: the page's CSS was inlined inside the content section and the page
 * rendered completely unstyled. Twill yields page content inside
 * `<div class="app" id="app">` — Vue's mount point — and Vue's template compiler
 * DISCARDS <style> elements it finds while compiling the in-DOM template. The
 * markup survived, the styling did not.
 *
 * The fix is the `extra_css` stack, which renders in <head>, outside the mount
 * point. This asserts position rather than mere presence, because a <style>
 * block that merely exists is exactly the broken state.
 */
it('renders the Plugins stylesheet in the head, above Vue\'s mount point', function () {
    $html = $this->actingAsTwillAdmin()
        ->get(twillAdmin().'/plugins')
        ->assertOk()
        ->getContent();

    $styleAt = strpos($html, '.yo-plugins__card');
    $mountAt = strpos($html, 'id="app"');

    expect($styleAt)->not->toBeFalse('The Plugins page stylesheet was not rendered at all.')
        ->and($mountAt)->not->toBeFalse()
        ->and($styleAt)->toBeLessThan(
            $mountAt,
            'The stylesheet renders inside the Vue mount point, where Vue strips it.'
        );
});

it('redirects the legacy settings URL to the native settings singleton', function () {
    // The settings screen became the SeoSettings Twill singleton; the old
    // /seo URL stays alive as a bookmark-friendly redirect.
    $this->actingAsTwillAdmin()
        ->get(twillSeoUrl())
        ->assertRedirect(route(config('twill.admin_route_name_prefix', 'twill.').'seoSetting'));
});

it('redirects an unauthenticated request to the Twill login', function () {
    $this->get(twillSeoUrl())->assertRedirect(route('twill.login.form'));
});

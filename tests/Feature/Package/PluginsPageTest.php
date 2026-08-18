<?php

use TwillSeo\PluginPage\TwillPluginServiceProvider;
use TwillSeo\TwillSeoServiceProvider;

it('registers our manifest on the shared Plugins registry', function () {
    $registry = app(TwillPluginServiceProvider::REGISTRY_BINDING);

    expect($registry)->toHaveKey('yotech-ai/twill-cms-seo-suite');

    $manifest = $registry['yotech-ai/twill-cms-seo-suite'];

    expect($manifest['name'])->toBe('Twill SEO')
        ->and($manifest['route'])->toBe(config('twill.admin_route_name_prefix', 'twill.').'seo.index');
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

it('serves the placeholder settings page to an authenticated admin', function () {
    $this->actingAsTwillAdmin()
        ->get(twillSeoUrl())
        ->assertOk()
        ->assertSee('Twill SEO');
});

it('redirects an unauthenticated request to the Twill login', function () {
    $this->get(twillSeoUrl())->assertRedirect(route('twill.login.form'));
});

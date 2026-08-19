<?php

use TwillSeo\Http\Controllers\AssetController;

/** Pulls the @json()-encoded config out of the mount div's data-twill-seo attribute. */
function decodeSettingsMountConfig(string $html): array
{
    preg_match('/data-twill-seo=\'(.*?)\'/s', $html, $matches);

    expect($matches)->toHaveCount(2, 'expected exactly one data-twill-seo attribute in the rendered HTML');

    return json_decode($matches[1], associative: true, flags: JSON_THROW_ON_ERROR);
}

it('renders the settings mount div with endpoints, csrf and the full bootstrap payload', function () {
    $html = $this->actingAsTwillAdmin()->get(twillSeoUrl())->assertOk()->getContent();

    expect($html)->toContain('data-twill-seo-mount="settings"');

    $config = decodeSettingsMountConfig($html);
    $prefix = config('twill.admin_route_name_prefix', 'twill.');

    expect($config['endpoints']['show'])->toBe(route($prefix.'seo.settings.show'))
        ->and($config['endpoints']['update'])->toBe(route($prefix.'seo.settings.update'))
        ->and($config['endpoints']['media'])->toBe(route($prefix.'seo.media.index'))
        ->and($config['csrf'])->toBeString()->not->toBe('');

    $bootstrap = $config['bootstrap'];

    expect($bootstrap['sections']['general']['entity_type'])->toBe('organization')
        ->and($bootstrap['sections']['features']['analysis'])->toBeTrue()
        ->and($bootstrap['sections']['advanced'])->toHaveKey('robots_default_directives')
        ->and(collect($bootstrap['registry'])->pluck('key')->all())->toBe(['articles', 'pages'])
        ->and($bootstrap['sections']['content_types'])->toHaveKeys(['articles', 'pages'])
        ->and($bootstrap['media'])->toBe(['logo' => null, 'default_share' => null]);
});

it('pushes the built CSS/JS asset tags into the surrounding layout', function () {
    $html = $this->actingAsTwillAdmin()->get(twillSeoUrl())->assertOk()->getContent();

    expect($html)->toContain(AssetController::url('twill-seo.css'))
        ->and($html)->toContain(AssetController::url('twill-seo.iife.js'));
});

// Guest-blocked coverage for this same route already lives in
// PluginsPageTest ("redirects an unauthenticated request to the Twill
// login") — not duplicated here.

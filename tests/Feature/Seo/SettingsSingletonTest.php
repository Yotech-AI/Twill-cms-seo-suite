<?php

use Illuminate\Support\Facades\DB;
use TwillSeo\Services\Settings\SeoSettings;
use TwillSeo\Twill\Capsules\SeoSettings\Models\SeoSetting;
use TwillSeo\Twill\Capsules\SeoSettings\Repositories\SeoSettingRepository;

/*
 * The settings screen is a native Twill singleton (SeoSettings capsule):
 * full-width native form, real media library, repository mapping the flat
 * field names onto the same four JSON columns the accessor always read.
 */

function settingsRoute(): string
{
    return route(config('twill.admin_route_name_prefix', 'twill.').'seoSetting');
}

it('serves the singleton edit screen with the native form fields', function () {
    SeoSetting::current();

    $html = $this->actingAsTwillAdmin()->get(settingsRoute())->assertOk()->getContent();

    expect($html)->toContain('general_site_name')
        ->and($html)->toContain('general_social_profiles')
        ->and($html)->toContain('feature_analysis')
        ->and($html)->toContain('ct_articles_title_template')
        ->and($html)->toContain('advanced_robots_default_directives')
        // The two media roles render through Twill's own Medias field — the
        // native media library, not a custom picker.
        ->and($html)->toContain(SeoSetting::LOGO_ROLE)
        ->and($html)->toContain(SeoSetting::DEFAULT_SHARE_ROLE);
});

it('redirects the old settings URL to the singleton', function () {
    SeoSetting::current();

    $this->actingAsTwillAdmin()
        ->get(twillSeoUrl())
        ->assertRedirect(settingsRoute());
});

it('maps the flat form fields onto the JSON columns, and the accessor reads them back', function () {
    $row = SeoSetting::current();

    app(SeoSettingRepository::class)->update($row->id, [
        'general_site_name' => 'YoTech',
        'general_separator' => '—',
        'general_entity_type' => 'person',
        'general_social_profiles' => "https://x.com/yotech\n\nhttps://linkedin.com/company/yotech\n",
        'feature_analysis' => false,
        'feature_og' => true,
        'ct_articles_title_template' => '{title} | YoTech',
        'ct_articles_sitemap' => false,
        'advanced_robots_default_directives' => 'max-snippet:-1, noarchive',
        'advanced_search_action_enabled' => true,
    ]);

    $settings = app(SeoSettings::class);
    $settings->refresh();

    expect($settings->siteName())->toBe('YoTech')
        ->and($settings->separator())->toBe('—')
        ->and($settings->entityType())->toBe('person')
        ->and($settings->socialProfiles())->toBe(['https://x.com/yotech', 'https://linkedin.com/company/yotech'])
        ->and($settings->feature('analysis'))->toBeFalse()
        ->and($settings->feature('og'))->toBeTrue()
        ->and($settings->titleTemplate('articles'))->toBe('{title} | YoTech')
        ->and($settings->sitemapEnabled('articles'))->toBeFalse()
        ->and($settings->robotsDefaults())->toBe(['max-snippet:-1', 'noarchive'])
        ->and($settings->searchActionEnabled())->toBeTrue();

    // The flat names never leak into the row as phantom attributes.
    expect(SeoSetting::current()->getAttributes())->not->toHaveKey('general_site_name');
});

it('clears a content-type template back to the fallback when saved empty', function () {
    $row = SeoSetting::current();
    $repository = app(SeoSettingRepository::class);

    $repository->update($row->id, ['ct_articles_title_template' => '{title} | X']);
    $repository->update($row->id, ['ct_articles_title_template' => '  ']);

    $settings = app(SeoSettings::class);
    $settings->refresh();

    expect($settings->titleTemplate('articles'))
        ->toBe((string) config('twill-seo.title.default_template'));
});

it('prefers a media role over the legacy JSON id in the accessor', function () {
    // Twill's real (config-named) media tables exist in the harness.
    $mediasTable = config('twill.medias_table', 'twill_medias');
    $mediablesTable = config('twill.mediables_table', 'twill_mediables');

    $row = SeoSetting::current();
    $row->general = ['logo_media_id' => 999];
    $row->save();

    $mediaId = DB::table($mediasTable)->insertGetId([
        'uuid' => 'settings/logo.png',
        'filename' => 'logo.png',
        'width' => 512,
        'height' => 512,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table($mediablesTable)->insert([
        'media_id' => $mediaId,
        'mediable_id' => $row->id,
        'mediable_type' => $row->getMorphClass(),
        'role' => SeoSetting::LOGO_ROLE,
        'crop' => 'default',
        'metadatas' => '{}',
        'locale' => 'en',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $settings = app(SeoSettings::class);
    $settings->refresh();

    expect($settings->logoMediaId())->toBe($mediaId);
});

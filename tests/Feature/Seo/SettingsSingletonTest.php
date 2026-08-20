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
        // Native pickers, not a custom popup: the logo through the file
        // library (SVG-friendly), the share image through the media library.
        ->and($html)->toContain(SeoSetting::LOGO_ROLE)
        ->and($html)->toContain(SeoSetting::DEFAULT_SHARE_ROLE)
        // The schema type is a curated dropdown, not a free Input — a typo
        // must not be able to produce invalid structured data.
        ->and($html)->toContain('ct_articles_schema_type')
        ->and($html)->toContain('BlogPosting');
});

it('merges an exotic stored schema type into the dropdown options', function () {
    $row = SeoSetting::current();
    $row->content_types = ['articles' => ['schema_type' => 'Recipe']];
    $row->save();

    $html = $this->actingAsTwillAdmin()->get(settingsRoute())->assertOk()->getContent();

    // Without the merge the Select would render valueless and the next save
    // would silently replace Recipe with whatever the browser defaulted to.
    expect($html)->toContain('Recipe');
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

it('prefers the logo file role over every media fallback, dropping dimensions', function () {
    $filesTable = config('twill.files_table', 'twill_files');
    $fileablesTable = config('twill.fileables_table', 'twill_fileables');
    $mediasTable = config('twill.medias_table', 'twill_medias');
    $mediablesTable = config('twill.mediables_table', 'twill_mediables');

    $row = SeoSetting::current();

    // A media-role logo AND a file-role logo attached at once — the file
    // (the settings screen's native picker since the Files switch) wins.
    $mediaId = DB::table($mediasTable)->insertGetId([
        'uuid' => 'settings/old-logo.png',
        'filename' => 'old-logo.png',
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

    $fileId = DB::table($filesTable)->insertGetId([
        'uuid' => 'settings/logo.svg',
        'filename' => 'logo.svg',
        'size' => 1234,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table($fileablesTable)->insert([
        'file_id' => $fileId,
        'fileable_id' => $row->id,
        'fileable_type' => $row->getMorphClass(),
        'role' => SeoSetting::LOGO_ROLE,
        // Deliberately NOT the app locale: the lookup must be role-only —
        // the logo is one site-wide asset whatever admin language it was
        // uploaded under.
        'locale' => 'nl',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $settings = app(SeoSettings::class);
    $settings->refresh();

    $logo = $settings->logo();

    // Files carry no pixel dimensions (an SVG often has none at all), so
    // the file branch is url-only; schema.org's width/height are optional.
    expect($logo)->not->toBeNull()
        ->and($logo['url'])->toContain('settings/logo.svg')
        ->and($logo)->not->toHaveKeys(['width', 'height']);
});

it('falls back to the media logo, dimensions included, when no file is attached', function () {
    $mediasTable = config('twill.medias_table', 'twill_medias');
    $mediablesTable = config('twill.mediables_table', 'twill_mediables');

    $row = SeoSetting::current();

    $mediaId = DB::table($mediasTable)->insertGetId([
        'uuid' => 'settings/media-logo.png',
        'filename' => 'media-logo.png',
        'width' => 512,
        'height' => 256,
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

    $logo = $settings->logo();

    expect($logo)->not->toBeNull()
        ->and($logo['url'])->toContain('media-logo.png')
        ->and($logo['width'])->toBe(512)
        ->and($logo['height'])->toBe(256);
});

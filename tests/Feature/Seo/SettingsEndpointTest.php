<?php

use A17\Twill\Models\Media;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use TwillSeo\Models\SeoSetting;
use TwillSeo\Tests\Fixtures\Models\Article;
use TwillSeo\Tests\Fixtures\Repositories\ArticleRepository;

beforeEach(function () {
    $this->articles = new ArticleRepository(new Article);

    config(['twill-seo.models.articles.url' => function (Article $model, string $locale): string {
        return "https://example.test/{$locale}/articles/{$model->id}";
    }]);
});

it('never returns 200 to a guest on GET, redirecting to the twill login instead', function () {
    $this->get(twillSeoUrl('settings'))->assertRedirect(route('twill.login.form'));
});

it('never returns 200 to a guest on PUT, redirecting to the twill login instead', function () {
    $this->put(twillSeoUrl('settings'), ['general' => ['site_name' => 'x']])
        ->assertRedirect(route('twill.login.form'));
});

it('returns the merged settings, the registry rows and media summaries on GET', function () {
    $this->actingAsTwillAdmin();

    $response = $this->getJson(twillSeoUrl('settings'))->assertOk();

    $response->assertJsonPath('sections.general.entity_type', 'organization')
        ->assertJsonPath('sections.features.analysis', true)
        ->assertJsonPath('sections.advanced.uninstall_remove_data', false)
        ->assertJsonPath('media.logo', null)
        ->assertJsonPath('media.default_share', null);

    $registry = $response->json('registry');
    expect(collect($registry)->pluck('key')->all())->toBe(['articles', 'pages'])
        ->and(collect($registry)->pluck('label')->all())->toBe(['Articles', 'Pages']);

    // content_types carries one row per registry key, pre-populated with the
    // registry's own default schema type when nothing has been saved yet.
    $response->assertJsonPath('sections.content_types.articles.schema_type', 'WebPage')
        ->assertJsonPath('sections.content_types.pages.schema_type', 'WebPage');
});

it('round-trips the general section through PUT, persisting via SeoSetting::current()', function () {
    $this->actingAsTwillAdmin();

    $this->putJson(twillSeoUrl('settings'), [
        'general' => [
            'site_name' => 'Acme Co',
            'tagline' => 'Widgets for all',
            'separator' => '|',
            'entity_type' => 'person',
            'entity_name' => 'Jane Doe',
            'social_profiles' => ['https://twitter.com/acme', 'https://facebook.com/acme'],
        ],
    ])->assertOk()
        ->assertJsonPath('sections.general.site_name', 'Acme Co')
        ->assertJsonPath('sections.general.entity_type', 'person')
        ->assertJsonPath('sections.general.social_profiles', ['https://twitter.com/acme', 'https://facebook.com/acme']);

    expect(SeoSetting::current()->general)->toMatchArray([
        'site_name' => 'Acme Co',
        'tagline' => 'Widgets for all',
        'separator' => '|',
        'entity_type' => 'person',
        'entity_name' => 'Jane Doe',
        'social_profiles' => ['https://twitter.com/acme', 'https://facebook.com/acme'],
    ]);
});

it('returns a media summary for the stored logo and default share ids, on both GET and PUT', function () {
    $logo = Media::query()->create(['uuid' => 'logo.jpg', 'filename' => 'logo.jpg', 'width' => 200, 'height' => 200]);
    $share = Media::query()->create(['uuid' => 'share.jpg', 'filename' => 'share.jpg', 'width' => 1200, 'height' => 630]);

    $this->actingAsTwillAdmin();

    $putResponse = $this->putJson(twillSeoUrl('settings'), [
        'general' => ['logo_media_id' => $logo->id, 'default_share_media_id' => $share->id],
    ])->assertOk();

    $putResponse->assertJsonPath('media.logo.id', $logo->id)
        ->assertJsonPath('media.logo.name', 'logo.jpg')
        ->assertJsonPath('media.default_share.id', $share->id)
        ->assertJsonPath('media.default_share.name', 'share.jpg');

    expect($putResponse->json('media.logo.thumbnail'))->toBeString()->toContain($logo->uuid);

    $getResponse = $this->getJson(twillSeoUrl('settings'))->assertOk();

    $getResponse->assertJsonPath('media.logo.id', $logo->id)
        ->assertJsonPath('media.default_share.id', $share->id)
        ->assertJsonPath('sections.general.logo_media_id', $logo->id)
        ->assertJsonPath('sections.general.default_share_media_id', $share->id);
});

it('round-trips the content_types section through PUT', function () {
    $this->actingAsTwillAdmin();

    $this->putJson(twillSeoUrl('settings'), [
        'content_types' => [
            'articles' => [
                'title_template' => '{title} | Articles',
                'description_template' => 'Read about {title}.',
                'schema_type' => 'Article',
                'sitemap' => false,
            ],
        ],
    ])->assertOk()
        ->assertJsonPath('sections.content_types.articles.title_template', '{title} | Articles')
        ->assertJsonPath('sections.content_types.articles.schema_type', 'Article')
        ->assertJsonPath('sections.content_types.articles.sitemap', false);

    expect(SeoSetting::current()->content_types['articles']['schema_type'])->toBe('Article');
});

it('rejects a content_types key that is not in the model registry, and it never reaches the stored JSON', function () {
    $this->actingAsTwillAdmin();

    $this->putJson(twillSeoUrl('settings'), [
        'content_types' => [
            'not_a_real_type' => ['sitemap' => true],
        ],
    ])->assertStatus(422)
        ->assertJsonValidationErrors('content_types', null);

    expect($this->getJson(twillSeoUrl('settings'))->json('sections.content_types.not_a_real_type'))->toBeNull()
        ->and(SeoSetting::current()->content_types)->toBeNull();
});

it('rejects a mixed content_types payload (one valid key, one unregistered) entirely, not just the bad key', function () {
    $this->actingAsTwillAdmin();

    $this->putJson(twillSeoUrl('settings'), [
        'content_types' => [
            'articles' => ['schema_type' => 'Article'],
            'not_a_real_type' => ['sitemap' => true],
        ],
    ])->assertStatus(422)
        ->assertJsonValidationErrors('content_types', null);

    expect(SeoSetting::current()->content_types)->toBeNull();
});

it('round-trips the features section through PUT', function () {
    $this->actingAsTwillAdmin();

    $this->putJson(twillSeoUrl('settings'), [
        'features' => [
            'analysis' => true,
            'sitemap' => false,
            'schema' => true,
            'og' => true,
            'twitter' => false,
            'hreflang' => true,
        ],
    ])->assertOk()
        ->assertJsonPath('sections.features.sitemap', false)
        ->assertJsonPath('sections.features.hreflang', true);

    expect(SeoSetting::current()->features['sitemap'])->toBeFalse();
});

it('round-trips the advanced section through PUT', function () {
    $this->actingAsTwillAdmin();

    $this->putJson(twillSeoUrl('settings'), [
        'advanced' => [
            'robots_default_directives' => ['max-snippet:-1', 'noarchive'],
            'search_action_enabled' => true,
            'search_url_template' => 'https://example.test/search?q={search_term_string}',
            'uninstall_remove_data' => true,
        ],
    ])->assertOk()
        ->assertJsonPath('sections.advanced.search_action_enabled', true)
        ->assertJsonPath('sections.advanced.uninstall_remove_data', true);

    expect(SeoSetting::current()->advanced['robots_default_directives'])->toBe(['max-snippet:-1', 'noarchive']);
});

it('leaves untouched sections alone when only one section is sent', function () {
    $this->actingAsTwillAdmin();

    $this->putJson(twillSeoUrl('settings'), ['general' => ['site_name' => 'First save']])->assertOk();
    $this->putJson(twillSeoUrl('settings'), ['features' => ['sitemap' => false]])->assertOk();

    expect(SeoSetting::current()->general['site_name'])->toBe('First save')
        ->and(SeoSetting::current()->features['sitemap'])->toBeFalse();
});

it('flushes the sitemap cache on save, since content_types/features can change what it includes', function () {
    $this->articles->create(['title' => ['en' => 'A'], 'published' => true]);

    $this->get('/sitemap-articles-1.xml')->assertOk();
    $this->get('/sitemap.xml')->assertOk();

    expect(Cache::has('twill-seo.sitemap.articles.1'))->toBeTrue()
        ->and(Cache::has('twill-seo.sitemap.index'))->toBeTrue();

    $this->actingAsTwillAdmin();
    $this->putJson(twillSeoUrl('settings'), ['general' => ['tagline' => 'Unrelated change']])->assertOk();

    expect(Cache::has('twill-seo.sitemap.articles.1'))->toBeFalse()
        ->and(Cache::has('twill-seo.sitemap.index'))->toBeFalse();
});

it('proves a separator saved via the endpoint wins over config in a rendered head title', function () {
    config(['twill-seo.title.separator' => '-']);

    $article = $this->articles->create(['title' => ['en' => 'My Article']]);

    $this->actingAsTwillAdmin();

    $this->putJson(twillSeoUrl('settings'), ['general' => ['separator' => '::']])->assertOk();

    // SeoSettings is a per-request singleton with a memo (see its own doc
    // comment) — refresh() must actually clear it for THIS SAME request to
    // render with the new separator instead of a stale one.
    $html = renderHeadHtml(':model="$article"', ['article' => $article->fresh()]);

    expect(titleTagContent($html))->toBe('My Article :: '.config('app.name'));
});

// Twill's own exception handler renders a ValidationException on an admin
// JSON request as a FLAT error bag ({"general.entity_type": [...]}), not
// Laravel's default {"message", "errors"} envelope — see AnalyzeEndpointTest
// for the same convention (responseKey: null means "the errors are the
// root").

it('rejects a bad entity_type with 422', function () {
    $this->actingAsTwillAdmin();

    $this->putJson(twillSeoUrl('settings'), ['general' => ['entity_type' => 'robot']])
        ->assertStatus(422)
        ->assertJsonValidationErrors('general.entity_type', null);
});

it('rejects non-array social_profiles with 422', function () {
    $this->actingAsTwillAdmin();

    $this->putJson(twillSeoUrl('settings'), ['general' => ['social_profiles' => 'not-an-array']])
        ->assertStatus(422)
        ->assertJsonValidationErrors('general.social_profiles', null);
});

it('rejects a non-boolean feature toggle with 422', function () {
    $this->actingAsTwillAdmin();

    $this->putJson(twillSeoUrl('settings'), ['features' => ['sitemap' => 'yes please']])
        ->assertStatus(422)
        ->assertJsonValidationErrors('features.sitemap', null);
});

it('wires the configured settings throttle onto the update route', function () {
    $route = collect(Route::getRoutes())->first(
        fn ($route) => $route->getName() === config('twill.admin_route_name_prefix', 'twill.').'seo.settings.update'
    );

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain('throttle:'.config('twill-seo.settings.throttle', '30,1'));
});

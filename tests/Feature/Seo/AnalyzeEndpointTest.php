<?php

use A17\Twill\Models\Block;
use Illuminate\Support\Facades\Route;
use TwillSeo\Contracts\ResolvedContent;
use TwillSeo\Contracts\SeoContentResolver;
use TwillSeo\Models\SeoSetting;
use TwillSeo\Tests\Fixtures\Blocks\FixtureContentBlock;
use TwillSeo\Tests\Fixtures\Models\Article;
use TwillSeo\Tests\Fixtures\Repositories\ArticleRepository;

beforeEach(function () {
    $this->articles = new ArticleRepository(new Article);
});

/**
 * A resolver stub for one test only: proves a registry `content` class
 * override actually replaces the default RenderedBlocksResolver for that
 * model type, resolved through the container rather than constructed by
 * hand — a real host resolver could just as well need its own dependencies.
 */
class StubContentResolver implements SeoContentResolver
{
    public function resolve(object $model, string $locale): ResolvedContent
    {
        return new ResolvedContent('<p>'.implode(' ', array_fill(0, 12, 'stubbed')).'</p>', 'stub-source');
    }
}

/**
 * Attaches one real content block so saved-mode PaperFactory has actual body
 * text to analyze — a fixture block registered as a manual component block
 * (see FixtureServiceProvider), with a Block row created directly rather than
 * driven through Twill's block form pipeline, per the brief.
 *
 * @param  array<string,string>  $textByLocale
 */
function attachFixtureBlock(Article $article, array $textByLocale, string $editorName = 'default', int $position = 1): void
{
    Block::create([
        'blockable_id' => $article->id,
        'blockable_type' => $article->getMorphClass(),
        'type' => FixtureContentBlock::getBlockIdentifier(),
        'position' => $position,
        'editor_name' => $editorName,
        'content' => ['text' => $textByLocale],
    ]);
}

it('analyzes blocks from the registry-configured named editors and ignores the rest', function () {
    // Hosts with named editors (a hero editor above a content editor) list
    // them in the registry; 'default' stops being consulted the moment the
    // host takes over the list.
    config(['twill-seo.models.articles.block_editors' => ['hero', 'content']]);

    $repository = new ArticleRepository(new Article);
    $article = $repository->create(['title' => ['en' => 'Editors', 'nl' => 'Editors']]);

    attachFixtureBlock($article, ['en' => '<p>Zebra paragraphs live in the hero editor of this page.</p>'], 'hero', 1);
    attachFixtureBlock($article, ['en' => '<p>The content editor adds a second paragraph of words.</p>'], 'content', 2);
    attachFixtureBlock($article, ['en' => '<p>Quagga text hides in the unconfigured default editor.</p>'], 'default', 3);

    $this->actingAsTwillAdmin();

    // 'zebra' exists only in the hero block: the introduction check finding
    // it proves the named editor was rendered, first, in reading order.
    $hero = $this->postJson(twillSeoUrl('analyze'), [
        'type' => 'articles',
        'id' => $article->id,
        'locale' => 'en',
        'fields' => ['keyphrase' => 'zebra'],
    ])->assertOk();

    $heroIntro = collect($hero->json('report.seo.results'))->firstWhere('id', 'introductionKeyword');
    expect($heroIntro['rating'])->toBe('good');

    // 'quagga' exists only in the default editor, which the registry no
    // longer lists — its absence proves unconfigured editors are excluded.
    $default = $this->postJson(twillSeoUrl('analyze'), [
        'type' => 'articles',
        'id' => $article->id,
        'locale' => 'en',
        'fields' => ['keyphrase' => 'quagga'],
    ])->assertOk();

    $defaultIntro = collect($default->json('report.seo.results'))->firstWhere('id', 'introductionKeyword');
    expect($defaultIntro['rating'])->toBe('bad');
});

it('never returns 200 to a guest, redirecting to the twill login instead', function () {
    // Deliberately a plain post() rather than postJson(): with no
    // "Accept: application/json" header, Laravel's AuthenticationException
    // handler redirects rather than answering 401 — same convention
    // PluginsPageTest's guest-GET assertion already relies on.
    $this->post(twillSeoUrl('analyze'), ['type' => 'articles', 'id' => 1, 'locale' => 'en'])
        ->assertRedirect(route('twill.login.form'));
});

it('rejects a type the model registry does not know with 422', function () {
    $this->actingAsTwillAdmin();

    // Twill's own exception handler (TwillServiceProvider::boot()) renders a
    // ValidationException for a Twill request as the flat error bag itself
    // ({"type": [...]}), not Laravel's default {"message", "errors"}
    // envelope — hence responseKey: null, meaning "the errors are the root".
    $this->postJson(twillSeoUrl('analyze'), ['type' => 'not-a-real-type', 'id' => 1, 'locale' => 'en'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('type', null);
});

it('404s on an id the registered model does not have', function () {
    $this->actingAsTwillAdmin();

    $this->postJson(twillSeoUrl('analyze'), ['type' => 'articles', 'id' => 999999, 'locale' => 'en'])
        ->assertNotFound();
});

it('rejects an overlong content_override field with 422', function () {
    $this->actingAsTwillAdmin();

    $this->postJson(twillSeoUrl('analyze'), [
        'type' => 'articles',
        'id' => 1,
        'locale' => 'en',
        'fields' => ['content_override' => str_repeat('a', 500_001)],
    ])->assertStatus(422)
        ->assertJsonValidationErrors('fields.content_override', null);
});

it('404s when the analysis feature is disabled via the settings ROW, even with config left true', function () {
    // Same DB-over-config 404 gate SitemapController already has for its own
    // feature — the endpoint must not exist at all once the settings admin
    // has switched analysis off, regardless of what config still says.
    SeoSetting::create(['id' => 1, 'features' => ['analysis' => false]]);

    $article = $this->articles->create(['title' => ['en' => 'Test Article']]);

    $this->actingAsTwillAdmin();

    $this->postJson(twillSeoUrl('analyze'), [
        'type' => 'articles',
        'id' => $article->id,
        'locale' => 'en',
    ])->assertNotFound();
});

it('analyzes normally when the settings row re-enables analysis over a config default of false', function () {
    config(['twill-seo.features.analysis' => false]);
    SeoSetting::create(['id' => 1, 'features' => ['analysis' => true]]);

    $article = $this->articles->create(['title' => ['en' => 'Test Article']]);

    $this->actingAsTwillAdmin();

    $this->postJson(twillSeoUrl('analyze'), [
        'type' => 'articles',
        'id' => $article->id,
        'locale' => 'en',
    ])->assertOk();
});

it('analyzes the saved content end to end when no fields are posted', function () {
    $article = $this->articles->create([
        'title' => ['en' => 'Test Article', 'nl' => 'Testartikel'],
        'seo_title' => ['en' => 'Test Article SEO title'],
        'seo_keyphrase' => ['en' => 'green tea'],
        'seo_description' => ['en' => str_repeat('a', 130)],
    ]);

    attachFixtureBlock($article, [
        'en' => '<p>Brewing green tea is a simple ritual once you know the right water temperature.</p>'
            .'<p>Green tea has been prepared this way for centuries, valued for its delicate flavor.</p>',
    ]);

    $this->actingAsTwillAdmin();

    $response = $this->postJson(twillSeoUrl('analyze'), [
        'type' => 'articles',
        'id' => $article->id,
        'locale' => 'en',
    ])->assertOk();

    $response->assertJsonPath('meta.mode', 'saved')
        ->assertJsonPath('meta.content_source', 'rendered_blocks');

    $report = $response->json('report');

    expect($report['seo']['score'])->toBeInt()
        ->and($report['seo']['results'])->not->toBeEmpty()
        // Content-dependent: the block's real prose, not just title/description.
        ->and($report['insights']['wordCount'])->toBeGreaterThan(20);

    // A keyphrase placed only inside the block's opening sentence proves the
    // rendered block text — not some fallback — is what the engine analyzed.
    $introduction = collect($report['seo']['results'])->firstWhere('id', 'introductionKeyword');
    expect($introduction['rating'])->toBe('good');
});

it('keeps analyzing a properly registered block when a sibling block has no registered type', function () {
    $article = $this->articles->create(['title' => ['en' => 'Test Article']]);

    attachFixtureBlock($article, ['en' => '<p>Real content from a properly registered block survives.</p>']);

    // BlockRenderer has no registered block matching this type at all —
    // RenderedBlocksResolver's per-block fault tolerance means this must not
    // take the sibling block's real content down with it (nor 500 the call).
    Block::create([
        'blockable_id' => $article->id,
        'blockable_type' => $article->getMorphClass(),
        'type' => 'not-a-registered-block-type',
        'position' => 2,
        'editor_name' => 'default',
        'content' => [],
    ]);

    $this->actingAsTwillAdmin();

    $response = $this->postJson(twillSeoUrl('analyze'), [
        'type' => 'articles',
        'id' => $article->id,
        'locale' => 'en',
    ])->assertOk();

    $response->assertJsonPath('meta.content_source', 'rendered_blocks');
    expect($response->json('report.insights.wordCount'))->toBeGreaterThan(0);
});

it('lets a registry content resolver override replace the default block/content_fields resolver', function () {
    config(['twill-seo.models.articles.content' => StubContentResolver::class]);

    $article = $this->articles->create(['title' => ['en' => 'Test Article']]);
    attachFixtureBlock($article, ['en' => '<p>This block must never be seen once a content resolver override is configured.</p>']);

    $this->actingAsTwillAdmin();

    $response = $this->postJson(twillSeoUrl('analyze'), [
        'type' => 'articles',
        'id' => $article->id,
        'locale' => 'en',
    ])->assertOk();

    // The stub's own source tag and word count prove it — not the real
    // resolver, which would report rendered_blocks and a different count —
    // is what actually ran.
    $response->assertJsonPath('meta.content_source', 'stub-source')
        ->assertJsonPath('report.insights.wordCount', 12);
});

it('scores a live-typed seo_title higher than the saved analysis without touching stored data', function () {
    $article = $this->articles->create([
        'title' => ['en' => 'Test Article'],
        // Saved title deliberately does not contain the keyphrase.
        'seo_title' => ['en' => 'A generic page title'],
        'seo_keyphrase' => ['en' => 'green tea'],
        'seo_description' => ['en' => str_repeat('a', 130)],
    ]);

    attachFixtureBlock($article, ['en' => '<p>Some ordinary filler content for the article body copy.</p>']);

    $this->actingAsTwillAdmin();

    $savedResults = $this->postJson(twillSeoUrl('analyze'), [
        'type' => 'articles',
        'id' => $article->id,
        'locale' => 'en',
    ])->assertOk()->json('report.seo.results');

    $liveResponse = $this->postJson(twillSeoUrl('analyze'), [
        'type' => 'articles',
        'id' => $article->id,
        'locale' => 'en',
        'fields' => ['seo_title' => 'Green tea brewing guide'],
    ])->assertOk();

    $liveResponse->assertJsonPath('meta.mode', 'live');

    $savedScore = collect($savedResults)->firstWhere('id', 'keyphraseInSEOTitle')['score'];
    $liveScore = collect($liveResponse->json('report.seo.results'))->firstWhere('id', 'keyphraseInSEOTitle')['score'];

    expect($liveScore)->toBeGreaterThan($savedScore)
        ->and($liveScore)->toBe(9);

    // A live preview call must never write back to storage.
    expect($article->fresh()->seo('en')->seo_title)->toBe('A generic page title');
});

it('honors a content_override live field, reflected in the word count', function () {
    $article = $this->articles->create(['title' => ['en' => 'Test Article']]);
    attachFixtureBlock($article, ['en' => '<p>This block text must be ignored once an override is posted.</p>']);

    $this->actingAsTwillAdmin();

    $response = $this->postJson(twillSeoUrl('analyze'), [
        'type' => 'articles',
        'id' => $article->id,
        'locale' => 'en',
        'fields' => ['content_override' => '<p>'.implode(' ', array_fill(0, 50, 'word')).'</p>'],
    ])->assertOk();

    $response->assertJsonPath('meta.mode', 'live')
        ->assertJsonPath('meta.content_source', 'override')
        ->assertJsonPath('report.insights.wordCount', 50);
});

it('flags a keyphrase already used by another entry in the same locale, but not across locales', function () {
    $articleA = $this->articles->create([
        'title' => ['en' => 'Article A', 'nl' => 'Artikel A'],
        'seo_keyphrase' => ['en' => 'alpha beta', 'nl' => 'alpha beta'],
    ]);
    $this->articles->create([
        'title' => ['en' => 'Article B'],
        'seo_keyphrase' => ['en' => 'alpha beta'],
    ]);

    $this->actingAsTwillAdmin();

    $enResults = $this->postJson(twillSeoUrl('analyze'), [
        'type' => 'articles',
        'id' => $articleA->id,
        'locale' => 'en',
    ])->assertOk()->json('report.seo.results');

    $nlResults = $this->postJson(twillSeoUrl('analyze'), [
        'type' => 'articles',
        'id' => $articleA->id,
        'locale' => 'nl',
    ])->assertOk()->json('report.seo.results');

    $enUsage = collect($enResults)->firstWhere('id', 'previouslyUsedKeyphrase');
    $nlUsage = collect($nlResults)->firstWhere('id', 'previouslyUsedKeyphrase');

    expect($enUsage['messageKey'])->toContain('used_once')
        ->and($nlUsage['messageKey'])->toContain('unique');
});

it('wires the configured analysis throttle onto the analyze route', function () {
    $route = collect(Route::getRoutes())->first(
        fn ($route) => $route->getName() === config('twill.admin_route_name_prefix', 'twill.').'seo.analyze'
    );

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain('throttle:'.config('twill-seo.analysis.throttle', '60,1'));
});

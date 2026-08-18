<?php

use A17\Twill\Models\Media;
use TwillSeo\Facades\TwillSeo;
use TwillSeo\Models\SeoSetting;
use TwillSeo\Tests\Fixtures\Models\Article;
use TwillSeo\Tests\Fixtures\Repositories\ArticleRepository;

// Article::OG_IMAGE_ROLE, not TwillSeo\Models\Behaviors\HasSeo::OG_IMAGE_ROLE
// directly: PHP forbids reading a trait constant via the trait name from
// outside a class that composes it (see HasSeo's own doc comment) — Article
// is such a class.

// renderHeadHtml(), metaContent(), linkHref(), titleTagContent() and
// attachMedia() are shared with SchemaGraphTest.php — see tests/Pest.php.

beforeEach(function () {
    $this->articles = new ArticleRepository(new Article);

    // A deterministic, always-resolving per-locale URL for every scenario
    // below except the ones specifically about URL resolution itself —
    // sidesteps Twill's own getFullUrl(), which has no real capsule/route
    // wired up for the bare test fixtures and returns '#' (UrlResolver's own
    // "unresolved" sentinel) in this harness.
    config(['twill-seo.models.articles.url' => function (Article $model, string $locale): string {
        return "https://example.test/{$locale}/articles/{$model->id}";
    }]);
});

it('uses a non-empty seo_title verbatim, with no template applied', function () {
    $article = $this->articles->create([
        'title' => ['en' => 'My Article'],
        'seo_title' => ['en' => 'Exact SEO Title'],
    ]);

    $html = renderHeadHtml(':model="$article"', ['article' => $article->fresh()]);

    expect(titleTagContent($html))->toBe('Exact SEO Title');
});

it('renders an empty seo_title through the title template using the SETTINGS ROW site name and separator, not config', function () {
    // DB-over-config precedence: config says one thing, the settings row
    // says another, and the row must win.
    config(['app.name' => 'Config App Name', 'twill-seo.title.separator' => '~']);

    SeoSetting::create(['id' => 1, 'general' => ['site_name' => 'DB Site Name', 'separator' => '|']]);

    $article = $this->articles->create(['title' => ['en' => 'My Article']]);

    $html = renderHeadHtml(':model="$article"', ['article' => $article->fresh()]);

    expect(titleTagContent($html))->toBe('My Article | DB Site Name');
});

it('escapes model-supplied text in the rendered title', function () {
    $article = $this->articles->create([
        'title' => ['en' => 'Article'],
        'seo_title' => ['en' => 'Tea & Crumpets <3'],
    ]);

    $html = renderHeadHtml(':model="$article"', ['article' => $article->fresh()]);

    expect(titleTagContent($html))->toBe('Tea & Crumpets <3')
        ->and($html)->toContain('Tea &amp; Crumpets &lt;3');
});

it('uses an explicit seo_description verbatim', function () {
    $article = $this->articles->create([
        'title' => ['en' => 'Article A'],
        'seo_description' => ['en' => 'An explicit description.'],
    ]);

    $html = renderHeadHtml(':model="$article"', ['article' => $article->fresh()]);

    expect(metaContent($html, 'name', 'description'))->toBe('An explicit description.');
});

it('renders the meta description from a settings content_types description_template when seo_description is empty', function () {
    config(['app.name' => 'Test Site']);

    SeoSetting::create(['id' => 1, 'content_types' => [
        'articles' => ['description_template' => 'Read {title} on {site_name}'],
    ]]);

    $article = $this->articles->create(['title' => ['en' => 'Article B']]);

    $html = renderHeadHtml(':model="$article"', ['article' => $article->fresh()]);

    expect(metaContent($html, 'name', 'description'))->toBe('Read Article B on Test Site');
});

it('omits the meta description entirely when there is no seo_description and no description_template', function () {
    $article = $this->articles->create(['title' => ['en' => 'Article C']]);

    $html = renderHeadHtml(':model="$article"', ['article' => $article->fresh()]);

    expect($html)->not->toContain('name="description"');
});

it('always emits robots meta, flipped by the noindex/nofollow flags', function () {
    $default = $this->articles->create(['title' => ['en' => 'A']]);
    $html = renderHeadHtml(':model="$article"', ['article' => $default->fresh()]);
    expect(metaContent($html, 'name', 'robots'))
        ->toBe('index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1');

    $flagged = $this->articles->create(['title' => ['en' => 'B'], 'seo_noindex' => true, 'seo_nofollow' => true]);
    $html = renderHeadHtml(':model="$article"', ['article' => $flagged->fresh()]);
    expect(metaContent($html, 'name', 'robots'))
        ->toBe('noindex, nofollow, max-snippet:-1, max-image-preview:large, max-video-preview:-1');
});

it('falls the canonical link back to the resolved URL, but an explicit canonical_url wins — and og:url always matches canonical, not the raw resolved URL', function () {
    $withoutOverride = $this->articles->create(['title' => ['en' => 'A']]);
    $html = renderHeadHtml(':model="$article"', ['article' => $withoutOverride->fresh()]);
    expect(linkHref($html, 'canonical'))->toBe('https://example.test/en/articles/'.$withoutOverride->id)
        ->and(metaContent($html, 'property', 'og:url'))->toBe('https://example.test/en/articles/'.$withoutOverride->id);

    $withOverride = $this->articles->create([
        'title' => ['en' => 'B'],
        'seo_canonical_url' => ['en' => 'https://example.test/custom-canonical'],
    ]);
    $html = renderHeadHtml(':model="$article"', ['article' => $withOverride->fresh()]);
    // og:url must follow the canonical override, not the raw resolved URL
    // (https://example.test/en/articles/{id}), which is what it would still
    // show if it read PageSeo::url instead of PageSeo::canonicalUrl.
    expect(linkHref($html, 'canonical'))->toBe('https://example.test/custom-canonical')
        ->and(metaContent($html, 'property', 'og:url'))->toBe('https://example.test/custom-canonical');
});

it('omits hreflang tags when the feature is off even though multiple locales would resolve', function () {
    config(['twill-seo.features.hreflang' => false]);

    $article = $this->articles->create(['title' => ['en' => 'A', 'nl' => 'A nl']]);
    $html = renderHeadHtml(':model="$article"', ['article' => $article->fresh()]);

    expect($html)->not->toContain('hreflang');
});

it('emits hreflang alternates plus x-default when the feature is on and at least two locales resolve', function () {
    config(['twill-seo.features.hreflang' => true]);

    $article = $this->articles->create(['title' => ['en' => 'A', 'nl' => 'A nl']]);
    $html = renderHeadHtml(':model="$article"', ['article' => $article->fresh()]);

    expect(linkHref($html, 'alternate', 'en'))->toBe('https://example.test/en/articles/'.$article->id)
        ->and(linkHref($html, 'alternate', 'nl'))->toBe('https://example.test/nl/articles/'.$article->id)
        // x-default points at the first CONFIGURED locale's URL (translatable.locales is ['en', 'nl'] — see TestCase).
        ->and(linkHref($html, 'alternate', 'x-default'))->toBe('https://example.test/en/articles/'.$article->id);
});

it('omits hreflang entirely when fewer than two locales resolve, even with the feature on', function () {
    config(['twill-seo.features.hreflang' => true, 'translatable.locales' => ['en']]);

    $article = $this->articles->create(['title' => ['en' => 'A']]);
    $html = renderHeadHtml(':model="$article"', ['article' => $article->fresh()]);

    expect($html)->not->toContain('hreflang');
});

it('emits OG tags with the mapped og:locale, og:type, og:url and og:site_name', function () {
    config(['app.name' => 'Test Site']);

    $article = $this->articles->create(['title' => ['en' => 'A', 'nl' => 'A nl']]);

    $html = renderHeadHtml(':model="$article"', ['article' => $article->fresh()]);
    expect(metaContent($html, 'property', 'og:locale'))->toBe('en_US')
        ->and(metaContent($html, 'property', 'og:type'))->toBe('website')
        ->and(metaContent($html, 'property', 'og:site_name'))->toBe('Test Site')
        ->and(metaContent($html, 'property', 'og:url'))->toBe('https://example.test/en/articles/'.$article->id)
        ->and(metaContent($html, 'property', 'og:title'))->not->toBeNull();

    $htmlNl = renderHeadHtml(':model="$article" locale="nl"', ['article' => $article->fresh()]);
    expect(metaContent($htmlNl, 'property', 'og:locale'))->toBe('nl_NL');
});

it('switches og:type to article and adds article:published_time/modified_time for an Article-ish schema type', function () {
    $article = $this->articles->create(['title' => ['en' => 'A']]);

    // Default registry schema_type ('WebPage', via ModelRegistry::DEFAULTS):
    // plain "website", no article: tags at all.
    $html = renderHeadHtml(':model="$article"', ['article' => $article->fresh()]);
    expect(metaContent($html, 'property', 'og:type'))->toBe('website')
        ->and($html)->not->toContain('article:published_time')
        ->not->toContain('article:modified_time');

    config(['twill-seo.models.articles.schema_type' => 'Article']);

    $html = renderHeadHtml(':model="$article"', ['article' => $article->fresh()]);
    expect(metaContent($html, 'property', 'og:type'))->toBe('article')
        // published/modified fall back to created_at/updated_at (see
        // SeoResolver::forModel) — the fixture articles table has no
        // published_at column, so created_at is what should show up here.
        ->and(metaContent($html, 'property', 'article:published_time'))
        ->toBe($article->created_at->format(DATE_ATOM))
        ->and(metaContent($html, 'property', 'article:modified_time'))
        ->toBe($article->fresh()->updated_at->format(DATE_ATOM));
});

it('flips og:type, adds article: meta tags, and adds the Article JSON-LD node when the Head component $type override makes the page Article-ish', function () {
    // Fix round (review finding #1): PageSeo::withOverrides() used to copy
    // ogType verbatim instead of recomputing it from the overridden schema
    // type, so <x-twill-seo::head :model="$item" type="Article" /> silently
    // did nothing to og:type, the article: tags, or the Article schema node
    // — even though the same override DID correctly update PageSeo::schemaType
    // (and JSON-LD's Article node's own @type). This is the Head component's
    // $type constructor param specifically, NOT SeoManager::page(schemaType:
    // ...), which derives ogType at construction and was never affected.

    // Default registry schema_type is 'WebPage' (ModelRegistry::DEFAULTS), so
    // without the override this page is not Article-ish at all.
    $article = $this->articles->create(['title' => ['en' => 'A']]);

    $html = renderHeadHtml(':model="$article"', ['article' => $article->fresh()]);
    expect(metaContent($html, 'property', 'og:type'))->toBe('website')
        ->and($html)->not->toContain('article:published_time');

    $htmlOverridden = renderHeadHtml(':model="$article" type="Article"', ['article' => $article->fresh()]);
    expect(metaContent($htmlOverridden, 'property', 'og:type'))->toBe('article')
        ->and(metaContent($htmlOverridden, 'property', 'article:published_time'))->not->toBeNull();

    $graph = renderJsonLd(':model="$article" type="Article"', ['article' => $article->fresh()]);
    expect(nodeOfType($graph, 'Article'))->not->toBeNull();
    // The WebPage node stays present too — Article is an ADDITIONAL node,
    // never a replacement (see WebPagePiece's own doc comment).
    expect(nodeOfType($graph, 'WebPage'))->not->toBeNull();
});

it('omits every og: tag when the og feature is off', function () {
    config(['twill-seo.features.og' => false]);

    $article = $this->articles->create(['title' => ['en' => 'A']]);
    $html = renderHeadHtml(':model="$article"', ['article' => $article->fresh()]);

    expect($html)->not->toContain('property="og:');
});

it('cascades the OG image: the HasSeo OG role wins over the registry image_role, which wins over the settings default share media', function () {
    $shareMedia = Media::query()->create(['uuid' => 'share-default.jpg', 'filename' => 'share.jpg', 'width' => 1000, 'height' => 500]);
    SeoSetting::create(['id' => 1, 'general' => ['default_share_media_id' => $shareMedia->id]]);

    config(['twill-seo.models.articles.image_role' => 'hero_image']);

    // Nothing attached at all yet: falls all the way to the settings default.
    $article = $this->articles->create(['title' => ['en' => 'A']])->fresh();
    $html = renderHeadHtml(':model="$article"', ['article' => $article]);
    expect(metaContent($html, 'property', 'og:image'))->toContain('share-default.jpg');

    // The registry's own image_role now wins over the settings default.
    $registryMedia = attachMedia($article, 'hero_image', 800, 400);
    $article = $article->fresh();
    $html = renderHeadHtml(':model="$article"', ['article' => $article]);
    expect(metaContent($html, 'property', 'og:image'))->toContain($registryMedia->uuid)
        ->and(metaContent($html, 'property', 'og:image:width'))->toBe('800')
        ->and(metaContent($html, 'property', 'og:image:height'))->toBe('400');

    // HasSeo's own dedicated OG role wins over the registry role.
    $ogMedia = attachMedia($article, Article::OG_IMAGE_ROLE, 1200, 630);
    $article = $article->fresh();
    $html = renderHeadHtml(':model="$article"', ['article' => $article]);
    expect(metaContent($html, 'property', 'og:image'))->toContain($ogMedia->uuid)
        ->and(metaContent($html, 'property', 'og:image:width'))->toBe('1200')
        ->and(metaContent($html, 'property', 'og:image:height'))->toBe('630');
});

it('shows twitter tags only when the twitter feature is on and they differ from OG, sizing the card by image presence', function () {
    config(['app.name' => 'Test Site']);

    // Nothing twitter-specific customized: OG is on, so nothing distinct to
    // show — no twitter block at all (Twitter falls back to og: tags itself).
    $plain = $this->articles->create(['title' => ['en' => 'Plain Article']]);
    $html = renderHeadHtml(':model="$article"', ['article' => $plain->fresh()]);
    expect($html)->not->toContain('twitter:card');

    // An explicit, distinct twitter_title: the block appears, card is
    // "summary" while there is no image.
    $customized = $this->articles->create([
        'title' => ['en' => 'Another Article'],
        'seo_twitter_title' => ['en' => 'Twitter-Specific Title'],
    ])->fresh();
    $html = renderHeadHtml(':model="$article"', ['article' => $customized]);
    expect(metaContent($html, 'name', 'twitter:title'))->toBe('Twitter-Specific Title')
        ->and(metaContent($html, 'name', 'twitter:card'))->toBe('summary');

    // Attaching an OG image flips the card to summary_large_image.
    attachMedia($customized, Article::OG_IMAGE_ROLE, 1200, 630);
    $html = renderHeadHtml(':model="$article"', ['article' => $customized->fresh()]);
    expect(metaContent($html, 'name', 'twitter:card'))->toBe('summary_large_image');

    // The twitter feature being off suppresses the block even with a
    // customization present.
    config(['twill-seo.features.twitter' => false]);
    $html = renderHeadHtml(':model="$article"', ['article' => $customized->fresh()]);
    expect($html)->not->toContain('twitter:card');
});

it('renders via TwillSeo::page() for a route with no model, through the default title template', function () {
    config(['app.name' => 'Test Site']);

    TwillSeo::page(
        title: 'Search Results',
        description: 'Find what you need.',
        url: 'https://example.test/search',
        noindex: true,
    );

    $html = renderHeadHtml();

    // Default separator ('-'): this test isn't about custom separators (see
    // the settings-row-precedence test above for that), just that page()'s
    // $title runs through the site's default title template at all.
    expect(titleTagContent($html))->toBe('Search Results - Test Site')
        ->and(metaContent($html, 'name', 'description'))->toBe('Find what you need.')
        ->and(metaContent($html, 'name', 'robots'))->toContain('noindex')
        ->and(linkHref($html, 'canonical'))->toBe('https://example.test/search');
});

it('lets the component-level title/description overrides win over the fully resolved values', function () {
    $article = $this->articles->create([
        'title' => ['en' => 'My Article'],
        'seo_title' => ['en' => 'Stored SEO Title'],
        'seo_description' => ['en' => 'Stored description.'],
    ]);

    $html = renderHeadHtml(
        ':model="$article" title="Hard Override Title" description="Hard override description."',
        ['article' => $article->fresh()]
    );

    expect(titleTagContent($html))->toBe('Hard Override Title')
        ->and(metaContent($html, 'name', 'description'))->toBe('Hard override description.');
});

it('renders nothing but a comment when there is no model and no prior TwillSeo::page() call', function () {
    $html = renderHeadHtml();

    expect(trim($html))->toStartWith('<!--')
        ->and($html)->not->toContain('<title>')
        ->and($html)->not->toContain('name="robots"');
});

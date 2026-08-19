<?php

use Illuminate\Support\Facades\Cache;
use TwillSeo\Services\Sitemap\SitemapCache;
use TwillSeo\Tests\Fixtures\Models\Article;
use TwillSeo\Tests\Fixtures\Models\Page;
use TwillSeo\Tests\Fixtures\Models\PlainModel;
use TwillSeo\Tests\Fixtures\Repositories\ArticleRepository;
use TwillSeo\Tests\Fixtures\Repositories\PageRepository;

// Locale note: the public sitemap routes carry no 'localization' middleware
// (see TwillSeoServiceProvider::registerPublicRoutes()), so app()->getLocale()
// stays whatever config('app.locale') resolves to for the whole request —
// 'en' under Testbench (vendor/orchestra/testbench-core/laravel/config/app.php)
// — which is also translatable.locales[0] (see TestCase::defineEnvironment).
// Every fixture URL below is built against that fixed 'en' primary locale.

beforeEach(function () {
    $this->articles = new ArticleRepository(new Article);
    $this->pages = new PageRepository(new Page);

    // A deterministic, always-resolving per-locale URL for articles — same
    // pattern HeadRenderTest/SchemaGraphTest use to sidestep Twill's own
    // getFullUrl(), which has no real capsule/route wired up for the bare
    // test fixtures and returns UrlResolver's own "unresolved" sentinel.
    config(['twill-seo.models.articles.url' => function (Article $model, string $locale): string {
        return "https://example.test/{$locale}/articles/{$model->id}";
    }]);
});

/**
 * @return list<string>
 */
function locsOf(SimpleXMLElement $xml): array
{
    return array_map('strval', $xml->xpath('//*[local-name()="loc"]'));
}

function sitemapXml(string $content): SimpleXMLElement
{
    $xml = simplexml_load_string($content);

    expect($xml)->not->toBeFalse();

    return $xml;
}

function articleUrl(int $id, string $locale = 'en'): string
{
    return "https://example.test/{$locale}/articles/{$id}";
}

/**
 * The <lastmod> text of the index's <sitemap> entry whose <loc> is exactly
 * $loc — ties an assertion to a SPECIFIC entry instead of assuming registry
 * iteration order puts it at a given array index.
 */
function lastmodFor(SimpleXMLElement $xml, string $loc): ?string
{
    foreach ($xml->xpath('//*[local-name()="sitemap"]') as $node) {
        $nodeLoc = (string) ($node->xpath('./*[local-name()="loc"]')[0] ?? '');

        if ($nodeLoc === $loc) {
            $lastmod = $node->xpath('./*[local-name()="lastmod"]')[0] ?? null;

            return $lastmod !== null ? (string) $lastmod : null;
        }
    }

    return null;
}

it('lists every enabled registry type in the sitemap index with a loc and a W3C lastmod, as application/xml', function () {
    config(['twill-seo.models.pages.url' => function (Page $model, string $locale): string {
        return "https://example.test/{$locale}/pages/{$model->id}";
    }]);

    $article = $this->articles->create(['title' => ['en' => 'A'], 'published' => true]);
    $this->pages->create(['title' => 'A page', 'published' => true]);

    $response = $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    $xml = sitemapXml($response->getContent());

    expect(locsOf($xml))
        ->toContain(url('sitemap-articles-1.xml'))
        ->toContain(url('sitemap-pages-1.xml'));

    $lastmod = lastmodFor($xml, url('sitemap-articles-1.xml'));
    expect($lastmod)
        ->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/')
        ->toBe($article->fresh()->updated_at->format(DATE_W3C));
});

it('excludes a disabled type from the sitemap index', function () {
    config(['twill-seo.models.pages.sitemap' => false]);
    config(['twill-seo.models.pages.url' => fn (Page $m, string $l): string => "https://example.test/{$l}/pages/{$m->id}"]);

    $this->articles->create(['title' => ['en' => 'A'], 'published' => true]);
    $this->pages->create(['title' => 'A page', 'published' => true]);

    $xml = sitemapXml($this->get('/sitemap.xml')->assertOk()->getContent());

    expect(locsOf($xml))
        ->toContain(url('sitemap-articles-1.xml'))
        ->not->toContain(url('sitemap-pages-1.xml'));
});

it('never lets a registered type without Twill publish scopes crash the index or its own page', function () {
    // PlainModel extends bare Illuminate\Database\Eloquent\Model, not
    // A17\Twill\Models\Model — it has neither scopePublished() nor
    // scopeVisible(). HasSeo is documented to support exactly this ("any
    // host Eloquent model, Twill module or not"); SitemapBuilder must
    // honor that rather than assuming every registered type has Twill's
    // publish scopes just because it's SEO-registered.
    config(['twill-seo.models.plain' => [
        'model' => PlainModel::class,
        'title_attribute' => 'title',
        'url' => fn (PlainModel $m, string $l): string => "https://example.test/{$l}/plain/{$m->id}",
    ]]);

    $this->articles->create(['title' => ['en' => 'A'], 'published' => true]);
    $plain = PlainModel::create(['title' => 'A plain row']);

    // The index still renders for everyone: the healthy 'articles' type is
    // present, and 'plain' is present too — with no published()/visible()
    // to filter by, every row of a scope-less type is eligible by that
    // axis (nothing to exclude on), so its one row is included rather than
    // silently dropped. Neither type crashes the shared response.
    $index = sitemapXml($this->get('/sitemap.xml')->assertOk()->getContent());

    expect(locsOf($index))
        ->toContain(url('sitemap-articles-1.xml'))
        ->toContain(url('sitemap-plain-1.xml'));

    // The scope-less type's own page also renders (not a 500) and lists
    // its row.
    $plainPage = sitemapXml($this->get('/sitemap-plain-1.xml')->assertOk()->getContent());

    expect(locsOf($plainPage))->toBe(["https://example.test/en/plain/{$plain->id}"]);
});

it('returns an empty but valid sitemap index when nothing is eligible yet', function () {
    $response = $this->get('/sitemap.xml')->assertOk();

    $xml = sitemapXml($response->getContent());

    expect($xml->xpath('//*[local-name()="sitemap"]'))->toHaveCount(0);
});

it('paginates a type across multiple sitemap files and 404s past the last page', function () {
    config(['twill-seo.sitemap.per_page' => 5]);

    foreach (range(1, 6) as $i) {
        $this->articles->create(['title' => ['en' => "Article {$i}"], 'published' => true]);
    }

    $index = sitemapXml($this->get('/sitemap.xml')->assertOk()->getContent());

    expect(locsOf($index))
        ->toContain(url('sitemap-articles-1.xml'))
        ->toContain(url('sitemap-articles-2.xml'))
        ->not->toContain(url('sitemap-articles-3.xml'));

    $page1 = sitemapXml($this->get('/sitemap-articles-1.xml')->assertOk()->getContent());
    $page2 = sitemapXml($this->get('/sitemap-articles-2.xml')->assertOk()->getContent());

    expect($page1->xpath('//*[local-name()="url"]'))->toHaveCount(5);
    expect($page2->xpath('//*[local-name()="url"]'))->toHaveCount(1);

    $this->get('/sitemap-articles-3.xml')->assertNotFound();
});

it('404s a type page for a known, enabled type that has zero eligible entries', function () {
    // No articles created at all: pageCount('articles') is 0, so even page 1
    // must 404 rather than render an empty-but-technically-valid urlset.
    $this->get('/sitemap-articles-1.xml')->assertNotFound();
});

it('lists only published articles with loc from the registry url callback and a W3C lastmod matching updated_at', function () {
    $published = $this->articles->create(['title' => ['en' => 'Published'], 'published' => true]);
    $this->articles->create(['title' => ['en' => 'Draft'], 'published' => false]);

    $xml = sitemapXml($this->get('/sitemap-articles-1.xml')->assertOk()->getContent());

    expect($xml->xpath('//*[local-name()="url"]'))->toHaveCount(1);
    expect(locsOf($xml))->toBe([articleUrl($published->id)]);

    $lastmods = $xml->xpath('//*[local-name()="lastmod"]');
    expect((string) $lastmods[0])->toBe($published->fresh()->updated_at->format(DATE_W3C));
});

it('excludes an article flagged robots_noindex', function () {
    $visible = $this->articles->create(['title' => ['en' => 'Visible'], 'published' => true]);
    $noindexed = $this->articles->create(['title' => ['en' => 'Noindexed'], 'published' => true, 'seo_noindex' => true]);

    $xml = sitemapXml($this->get('/sitemap-articles-1.xml')->assertOk()->getContent());

    expect(locsOf($xml))
        ->toContain(articleUrl($visible->id))
        ->not->toContain(articleUrl($noindexed->id));
});

it('excludes a model whose url resolver returns null', function () {
    $visible = $this->articles->create(['title' => ['en' => 'Visible'], 'published' => true]);
    $urlLess = $this->articles->create(['title' => ['en' => 'No URL'], 'published' => true]);

    config(['twill-seo.models.articles.url' => function (Article $model, string $locale) use ($urlLess): ?string {
        return $model->id === $urlLess->id ? null : articleUrl($model->id, $locale);
    }]);

    $xml = sitemapXml($this->get('/sitemap-articles-1.xml')->assertOk()->getContent());

    expect(locsOf($xml))
        ->toContain(articleUrl($visible->id))
        ->not->toContain(articleUrl($urlLess->id));
});

it('omits hreflang alternates when the feature is off', function () {
    $this->articles->create(['title' => ['en' => 'A', 'nl' => 'A nl'], 'published' => true]);

    $xml = sitemapXml($this->get('/sitemap-articles-1.xml')->assertOk()->getContent());
    $xml->registerXPathNamespace('xhtml', 'http://www.w3.org/1999/xhtml');

    expect($xml->xpath('//xhtml:link'))->toHaveCount(0);
});

it('includes hreflang alternates for every resolving locale when the feature is on', function () {
    config(['twill-seo.features.hreflang' => true]);

    $article = $this->articles->create(['title' => ['en' => 'A', 'nl' => 'A nl'], 'published' => true]);

    $xml = sitemapXml($this->get('/sitemap-articles-1.xml')->assertOk()->getContent());
    $xml->registerXPathNamespace('xhtml', 'http://www.w3.org/1999/xhtml');

    $links = $xml->xpath('//xhtml:link');
    $byHreflang = [];
    foreach ($links as $link) {
        $byHreflang[(string) $link['hreflang']] = (string) $link['href'];
    }

    expect($byHreflang)->toBe([
        'en' => articleUrl($article->id, 'en'),
        'nl' => articleUrl($article->id, 'nl'),
    ]);
});

it('omits hreflang entirely when fewer than two locales resolve, even with the feature on', function () {
    config(['twill-seo.features.hreflang' => true, 'translatable.locales' => ['en']]);

    $this->articles->create(['title' => ['en' => 'A'], 'published' => true]);

    $xml = sitemapXml($this->get('/sitemap-articles-1.xml')->assertOk()->getContent());
    $xml->registerXPathNamespace('xhtml', 'http://www.w3.org/1999/xhtml');

    expect($xml->xpath('//xhtml:link'))->toHaveCount(0);
});

it('omits hreflang entirely when every configured locale resolves to the identical URL', function () {
    // Overrides this file's own beforeEach, which builds a distinct URL per
    // locale — here the url callback ignores $locale entirely, so both
    // configured locales resolve to the exact same string.
    config(['twill-seo.features.hreflang' => true]);
    config(['twill-seo.models.articles.url' => fn (Article $m, string $l): string => "https://example.test/articles/{$m->id}"]);

    $this->articles->create(['title' => ['en' => 'A', 'nl' => 'A nl'], 'published' => true]);

    $xml = sitemapXml($this->get('/sitemap-articles-1.xml')->assertOk()->getContent());
    $xml->registerXPathNamespace('xhtml', 'http://www.w3.org/1999/xhtml');

    expect($xml->xpath('//xhtml:link'))->toHaveCount(0);
});

it('omits image entries when sitemap_images is off for the type', function () {
    $article = $this->articles->create(['title' => ['en' => 'A'], 'published' => true]);
    attachMedia($article, Article::OG_IMAGE_ROLE, 1200, 630);

    $xml = sitemapXml($this->get('/sitemap-articles-1.xml')->assertOk()->getContent());
    $xml->registerXPathNamespace('image', 'http://www.google.com/schemas/sitemap-image/1.1');

    expect($xml->xpath('//image:image'))->toHaveCount(0);
});

it('includes an image:image entry with the resolved OG image url when sitemap_images is on', function () {
    config(['twill-seo.models.articles.sitemap_images' => true]);

    $article = $this->articles->create(['title' => ['en' => 'A'], 'published' => true]);
    $media = attachMedia($article, Article::OG_IMAGE_ROLE, 1200, 630);

    $xml = sitemapXml($this->get('/sitemap-articles-1.xml')->assertOk()->getContent());
    $xml->registerXPathNamespace('image', 'http://www.google.com/schemas/sitemap-image/1.1');

    $imageLocs = array_map('strval', $xml->xpath('//image:loc'));

    expect($imageLocs)->toHaveCount(1);
    expect($imageLocs[0])->toContain($media->uuid);
});

it('404s the index and any type page when the sitemap feature is off', function () {
    config(['twill-seo.features.sitemap' => false]);

    $this->articles->create(['title' => ['en' => 'A'], 'published' => true]);

    $this->get('/sitemap.xml')->assertNotFound();
    $this->get('/sitemap-articles-1.xml')->assertNotFound();
});

it('404s for an unknown type, a disabled type, and page zero', function () {
    $this->articles->create(['title' => ['en' => 'A'], 'published' => true]);

    $this->get('/sitemap-bogus-1.xml')->assertNotFound();
    $this->get('/sitemap-articles-0.xml')->assertNotFound();

    config(['twill-seo.models.articles.sitemap' => false]);
    $this->get('/sitemap-articles-1.xml')->assertNotFound();
});

it('serves a cached document without recomputing it, under the documented cache key scheme', function () {
    $this->articles->create(['title' => ['en' => 'A'], 'published' => true]);

    // A page must exist (pageCount() >= 1) for the controller to get past its
    // range check at all — once it does, overwriting the cache key directly
    // with a document a real render could never produce (this exact <loc>,
    // since the configured url callback always builds
    // ".../articles/{id}") proves the controller reads from that literal
    // key rather than recomputing, and pins the key naming scheme the
    // brief specifies. The sentinel is a real (if fake) <url> block rather
    // than an XML comment specifically so it survives simplexml parsing —
    // SimpleXML does not expose comment text through its normal API.
    Cache::put(
        'twill-seo.sitemap.articles.1',
        '<urlset><url><loc>https://example.test/cached-sentinel-page.xml</loc></url></urlset>',
        3600,
    );

    $pageXml = sitemapXml($this->get('/sitemap-articles-1.xml')->assertOk()->getContent());

    expect(locsOf($pageXml))->toBe(['https://example.test/cached-sentinel-page.xml']);

    Cache::put(
        'twill-seo.sitemap.index',
        '<sitemapindex><sitemap><loc>https://example.test/cached-sentinel-index.xml</loc></sitemap></sitemapindex>',
        3600,
    );

    $indexXml = sitemapXml($this->get('/sitemap.xml')->assertOk()->getContent());

    expect(locsOf($indexXml))->toBe(['https://example.test/cached-sentinel-index.xml']);
});

it('forgets the type and index cache when an article is saved through the repository, reflecting the new article on re-render', function () {
    $this->articles->create(['title' => ['en' => 'First'], 'published' => true]);

    $this->get('/sitemap-articles-1.xml')->assertOk();
    $this->get('/sitemap.xml')->assertOk();

    expect(Cache::has('twill-seo.sitemap.articles.1'))->toBeTrue();
    expect(Cache::has('twill-seo.sitemap.index'))->toBeTrue();

    $second = $this->articles->create(['title' => ['en' => 'Second'], 'published' => true]);

    expect(Cache::has('twill-seo.sitemap.articles.1'))->toBeFalse();
    expect(Cache::has('twill-seo.sitemap.index'))->toBeFalse();

    $xml = sitemapXml($this->get('/sitemap-articles-1.xml')->assertOk()->getContent());

    expect(locsOf($xml))->toContain(articleUrl($second->id));
});

it('forgets every previously cached page for a type, not just the first, on the next save', function () {
    config(['twill-seo.sitemap.per_page' => 5]);

    foreach (range(1, 6) as $i) {
        $this->articles->create(['title' => ['en' => "Article {$i}"], 'published' => true]);
    }

    $this->get('/sitemap-articles-1.xml')->assertOk();
    $this->get('/sitemap-articles-2.xml')->assertOk();

    expect(Cache::has('twill-seo.sitemap.articles.1'))->toBeTrue();
    expect(Cache::has('twill-seo.sitemap.articles.2'))->toBeTrue();

    $this->articles->create(['title' => ['en' => 'Seventh'], 'published' => true]);

    expect(Cache::has('twill-seo.sitemap.articles.1'))->toBeFalse();
    expect(Cache::has('twill-seo.sitemap.articles.2'))->toBeFalse();
});

it('forgets the cache when an article is deleted through the repository', function () {
    $article = $this->articles->create(['title' => ['en' => 'A'], 'published' => true]);

    $this->get('/sitemap-articles-1.xml')->assertOk();
    expect(Cache::has('twill-seo.sitemap.articles.1'))->toBeTrue();

    $this->articles->delete($article->id);

    expect(Cache::has('twill-seo.sitemap.articles.1'))->toBeFalse();
});

it('flushes every tracked type and the index via SitemapCache::flushAll()', function () {
    config(['twill-seo.models.pages.url' => fn (Page $m, string $l): string => "https://example.test/{$l}/pages/{$m->id}"]);

    $this->articles->create(['title' => ['en' => 'A'], 'published' => true]);
    $this->pages->create(['title' => 'A page', 'published' => true]);

    $this->get('/sitemap-articles-1.xml')->assertOk();
    $this->get('/sitemap-pages-1.xml')->assertOk();
    $this->get('/sitemap.xml')->assertOk();

    app(SitemapCache::class)->flushAll();

    expect(Cache::has('twill-seo.sitemap.articles.1'))->toBeFalse();
    expect(Cache::has('twill-seo.sitemap.pages.1'))->toBeFalse();
    expect(Cache::has('twill-seo.sitemap.index'))->toBeFalse();
});

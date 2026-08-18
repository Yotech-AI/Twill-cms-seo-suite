<?php

use A17\Twill\Models\Media;
use Illuminate\Support\Facades\Event;
use TwillSeo\Contracts\GraphPiece;
use TwillSeo\Events\BuildingSchemaGraph;
use TwillSeo\Models\SeoSetting;
use TwillSeo\Services\Schema\SchemaContext;
use TwillSeo\Services\Settings\SeoSettings;
use TwillSeo\Tests\Fixtures\Models\Article;
use TwillSeo\Tests\Fixtures\Repositories\ArticleRepository;

/**
 * A config-registered custom schema piece (twill-seo.schema.pieces) — a
 * plain 'Thing' node with a fixed @id, just to prove the config wiring
 * carries it into the final graph.
 */
class SchemaGraphTestCustomPiece implements GraphPiece
{
    public function pieces(SchemaContext $context): array
    {
        return [['@type' => 'Thing', '@id' => 'custom-piece-node', 'name' => 'Custom']];
    }
}

// renderJsonLd() and nodeOfType() are shared with HeadRenderTest.php — see
// tests/Pest.php.

beforeEach(function () {
    $this->articles = new ArticleRepository(new Article);

    config(['twill-seo.models.articles.url' => function (Article $model, string $locale): string {
        return "https://example.test/{$locale}/articles/{$model->id}";
    }]);
});

it('wraps every piece in a single @context/@graph document', function () {
    $article = $this->articles->create(['title' => ['en' => 'A']]);

    $graph = renderJsonLd(':model="$article"', ['article' => $article->fresh()]);

    expect($graph['@context'])->toBe('https://schema.org')
        ->and($graph['@graph'])->toBeArray()
        ->not->toBeEmpty();
});

it('never lets a title containing "</script>" break out of the JSON-LD script tag', function () {
    config(['app.name' => 'Test Site']);

    // A raw model title (not seo_title, which the resolver would use
    // verbatim) flows into WebPagePiece's own 'name' field, so this pins
    // json_encode()'s JSON_HEX_TAG flag end to end, not just in isolation —
    // it also runs through the default title template, hence the " - Test
    // Site" suffix in the expected value below.
    $article = $this->articles->create(['title' => ['en' => '</script><script>alert(1)</script>']]);

    $html = renderHeadHtml(':model="$article"', ['article' => $article->fresh()]);

    // Unescaped, the malicious title's own two closing-script occurrences
    // would add up with the real one to three; JSON_HEX_TAG rewrites every
    // angle bracket inside the JSON string to a numeric character
    // reference, so only the genuine closing tag of the ld+json script
    // element survives as literal text.
    expect(substr_count($html, '</script>'))->toBe(1);

    // The JSON must still parse cleanly and round-trip the exact original
    // value — escaping must not have corrupted the data itself.
    $graph = renderJsonLd(':model="$article"', ['article' => $article->fresh()]);
    expect(nodeOfType($graph, 'WebPage')['name'])->toBe('</script><script>alert(1)</script> - Test Site');
});

it('omits the JSON-LD script entirely when the schema feature is off', function () {
    config(['twill-seo.features.schema' => false]);

    $article = $this->articles->create(['title' => ['en' => 'A']]);

    expect(renderJsonLd(':model="$article"', ['article' => $article->fresh()]))->toBeNull();
});

it('emits an Organization node by default, sourced from settings entity fields', function () {
    SeoSetting::create(['id' => 1, 'general' => ['entity_type' => 'organization', 'entity_name' => 'Acme Co']]);

    $article = $this->articles->create(['title' => ['en' => 'A']]);
    $graph = renderJsonLd(':model="$article"', ['article' => $article->fresh()]);

    $organization = nodeOfType($graph, 'Organization');
    expect($organization)->not->toBeNull()
        ->and($organization['name'])->toBe('Acme Co');
    expect(nodeOfType($graph, 'Person'))->toBeNull();
});

it('emits a Person node instead when settings entityType is person', function () {
    SeoSetting::create(['id' => 1, 'general' => ['entity_type' => 'person', 'entity_name' => 'Jane Doe']]);

    $article = $this->articles->create(['title' => ['en' => 'A']]);
    $graph = renderJsonLd(':model="$article"', ['article' => $article->fresh()]);

    $person = nodeOfType($graph, 'Person');
    expect($person)->not->toBeNull()
        ->and($person['name'])->toBe('Jane Doe');
    expect(nodeOfType($graph, 'Organization'))->toBeNull();
});

it('adds a logo and sameAs to the Organization node when settings provide a logo media id and social profiles', function () {
    $logoMedia = Media::query()->create(['uuid' => 'logo.jpg', 'filename' => 'logo.jpg', 'width' => 512, 'height' => 512]);

    SeoSetting::create(['id' => 1, 'general' => [
        'entity_type' => 'organization',
        'entity_name' => 'Acme Co',
        'logo_media_id' => $logoMedia->id,
        'social_profiles' => ['https://twitter.com/acme', 'https://facebook.com/acme'],
    ]]);

    $article = $this->articles->create(['title' => ['en' => 'A']]);
    $graph = renderJsonLd(':model="$article"', ['article' => $article->fresh()]);
    $organization = nodeOfType($graph, 'Organization');

    expect($organization['logo']['@type'])->toBe('ImageObject')
        ->and($organization['logo']['url'])->toContain('logo.jpg')
        ->and($organization['logo']['width'])->toBe(512)
        ->and($organization['logo']['height'])->toBe(512)
        ->and($organization['sameAs'])->toBe(['https://twitter.com/acme', 'https://facebook.com/acme']);
});

it('omits logo and sameAs from the Organization node when settings provide neither', function () {
    SeoSetting::create(['id' => 1, 'general' => ['entity_type' => 'organization', 'entity_name' => 'Acme Co']]);

    $article = $this->articles->create(['title' => ['en' => 'A']]);
    $graph = renderJsonLd(':model="$article"', ['article' => $article->fresh()]);
    $organization = nodeOfType($graph, 'Organization');

    expect($organization)->not->toHaveKey('logo')
        ->not->toHaveKey('sameAs');
});

it('includes a WebSite SearchAction only when settings searchActionEnabled is on', function () {
    $article = $this->articles->create(['title' => ['en' => 'A']]);

    $graph = renderJsonLd(':model="$article"', ['article' => $article->fresh()]);
    expect(nodeOfType($graph, 'WebSite'))->not->toHaveKey('potentialAction');

    // updateOrCreate, not create(['id' => 1, ...]): the render just above
    // already lazily created the id=1 row (SeoSetting::current()'s own
    // firstOrCreate()), so an explicit create() with the same id would
    // violate the unique constraint. refresh() clears SeoSettings' own
    // per-request memo of that now-stale (row-with-no-'advanced') fetch.
    SeoSetting::query()->updateOrCreate(['id' => 1], ['advanced' => [
        'search_action_enabled' => true,
        'search_url_template' => 'https://example.test/search?q={search_term_string}',
    ]]);
    app(SeoSettings::class)->refresh();

    $graph = renderJsonLd(':model="$article"', ['article' => $article->fresh()]);
    $website = nodeOfType($graph, 'WebSite');

    expect($website['potentialAction']['@type'])->toBe('SearchAction')
        ->and($website['potentialAction']['target']['urlTemplate'])->toBe('https://example.test/search?q={search_term_string}')
        ->and($website['potentialAction']['query-input'])->toBe('required name=search_term_string');
});

it('emits only a WebPage node for the default schema type, and also an Article node for an Article-ish type', function () {
    $article = $this->articles->create(['title' => ['en' => 'A']]);

    $graph = renderJsonLd(':model="$article"', ['article' => $article->fresh()]);
    expect(nodeOfType($graph, 'WebPage'))->not->toBeNull();
    expect(nodeOfType($graph, 'Article'))->toBeNull();

    config(['twill-seo.models.articles.schema_type' => 'Article']);

    $graph = renderJsonLd(':model="$article"', ['article' => $article->fresh()]);
    $webpage = nodeOfType($graph, 'WebPage');
    $articleNode = nodeOfType($graph, 'Article');

    expect($webpage)->not->toBeNull()
        ->and($articleNode)->not->toBeNull()
        ->and($articleNode['mainEntityOfPage']['@id'])->toBe($webpage['@id']);
});

it('defaults the breadcrumb list to Home -> current page with no url on the last item', function () {
    $article = $this->articles->create(['title' => ['en' => 'Breadcrumb Article']]);

    $graph = renderJsonLd(':model="$article"', ['article' => $article->fresh()]);
    $breadcrumb = nodeOfType($graph, 'BreadcrumbList');

    expect($breadcrumb['itemListElement'])->toHaveCount(2)
        ->and($breadcrumb['itemListElement'][0]['name'])->toBe('Home')
        ->and($breadcrumb['itemListElement'][0])->toHaveKey('item')
        ->and($breadcrumb['itemListElement'][1]['name'])->toBe('Breadcrumb Article')
        ->and($breadcrumb['itemListElement'][1])->not->toHaveKey('item');
});

it('uses the registry breadcrumbs callback instead of the default when one is configured', function () {
    config(['twill-seo.models.articles.breadcrumbs' => function (Article $model, string $locale): array {
        return [['Blog', 'https://example.test/blog'], ['Custom Crumb', null]];
    }]);

    $article = $this->articles->create(['title' => ['en' => 'Breadcrumb Article']]);
    $graph = renderJsonLd(':model="$article"', ['article' => $article->fresh()]);
    $breadcrumb = nodeOfType($graph, 'BreadcrumbList');

    expect($breadcrumb['itemListElement'][0]['name'])->toBe('Blog')
        ->and($breadcrumb['itemListElement'][0]['item'])->toBe('https://example.test/blog')
        ->and($breadcrumb['itemListElement'][1]['name'])->toBe('Custom Crumb')
        ->and($breadcrumb['itemListElement'][1])->not->toHaveKey('item');
});

it('cross-references nodes consistently by @id', function () {
    SeoSetting::create(['id' => 1, 'general' => ['entity_type' => 'organization', 'entity_name' => 'Acme Co']]);
    config(['twill-seo.models.articles.schema_type' => 'Article']);

    $article = $this->articles->create(['title' => ['en' => 'A']]);
    // Article::OG_IMAGE_ROLE, not HasSeo::OG_IMAGE_ROLE directly — see the
    // same note in HeadRenderTest.php.
    attachMedia($article->fresh(), Article::OG_IMAGE_ROLE, 1200, 630);

    $graph = renderJsonLd(':model="$article"', ['article' => $article->fresh()]);

    $organization = nodeOfType($graph, 'Organization');
    $website = nodeOfType($graph, 'WebSite');
    $webpage = nodeOfType($graph, 'WebPage');
    $articleNode = nodeOfType($graph, 'Article');
    $breadcrumb = nodeOfType($graph, 'BreadcrumbList');
    $image = nodeOfType($graph, 'ImageObject');

    expect($website['publisher']['@id'])->toBe($organization['@id'])
        ->and($webpage['isPartOf']['@id'])->toBe($website['@id'])
        ->and($webpage['breadcrumb']['@id'])->toBe($breadcrumb['@id'])
        ->and($webpage['primaryImageOfPage']['@id'])->toBe($image['@id'])
        ->and($articleNode['isPartOf']['@id'])->toBe($webpage['@id'])
        ->and($articleNode['mainEntityOfPage']['@id'])->toBe($webpage['@id'])
        ->and($articleNode['publisher']['@id'])->toBe($organization['@id'])
        ->and($articleNode['image']['@id'])->toBe($image['@id']);
});

it('omits primaryImageOfPage and the ImageObject node when no OG image resolves', function () {
    $article = $this->articles->create(['title' => ['en' => 'A']]);

    $graph = renderJsonLd(':model="$article"', ['article' => $article->fresh()]);
    $webpage = nodeOfType($graph, 'WebPage');

    expect($webpage)->not->toHaveKey('primaryImageOfPage');
    expect(nodeOfType($graph, 'ImageObject'))->toBeNull();
});

it('includes a piece registered via config twill-seo.schema.pieces', function () {
    config(['twill-seo.schema.pieces' => [SchemaGraphTestCustomPiece::class]]);

    $article = $this->articles->create(['title' => ['en' => 'A']]);
    $graph = renderJsonLd(':model="$article"', ['article' => $article->fresh()]);

    expect(nodeOfType($graph, 'Thing'))->not->toBeNull();
});

it('lets a BuildingSchemaGraph listener append a node before serialization', function () {
    Event::listen(BuildingSchemaGraph::class, function (BuildingSchemaGraph $event): void {
        $event->graph[] = ['@type' => 'Thing', '@id' => 'event-added-node', 'name' => 'From event'];
    });

    $article = $this->articles->create(['title' => ['en' => 'A']]);
    $graph = renderJsonLd(':model="$article"', ['article' => $article->fresh()]);

    $node = nodeOfType($graph, 'Thing');
    expect($node)->not->toBeNull()
        ->and($node['name'])->toBe('From event');
});

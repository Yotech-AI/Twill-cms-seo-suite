# Integration recipes

Everything below is an escape hatch or an extension point for a host that
needs more than the default wiring in the README. None of it is required —
a registered model with no `url`/`content`/`breadcrumbs` callback, no
`SeoLinkable` implementation and no extra schema piece still gets a full,
correct head render out of the box.

## `SeoLinkable`: full control over a model's own per-locale URL

`UrlResolver` resolves a model's URL through a fixed cascade: the registry's
own `url` callback, then `SeoLinkable::getSeoUrl()`, then Twill's
`getFullUrl()`. Implement `SeoLinkable` when a model's public route lives
outside Twill's controller/slug machinery entirely — a resource served by a
completely custom router, say — and you would rather keep the logic on the
model than write a registry callback.

```php
use TwillSeo\Contracts\SeoLinkable;

class Article extends Model implements SeoLinkable
{
    public function getSeoUrl(string $locale): ?string
    {
        $slug = $this->translations->firstWhere('locale', $locale)?->slug;

        return $slug === null ? null : route('articles.show', ['locale' => $locale, 'slug' => $slug]);
    }
}
```

The one thing `SeoLinkable` can do that a registry `url` callback cannot:
`getSeoUrl($locale)` receives the **locale being resolved for**, not the
ambient one. That is what makes it the right tool for genuinely correct
hreflang alternates — Twill's own `getFullUrl()` always reads
`app()->getLocale()` and ignores whatever locale a caller actually wants.

A registry `url` entry, when present, is checked first and always wins —
`SeoLinkable` is for a model with no such entry, or one whose entry you
would rather not touch.

## A custom `SeoContentResolver`

The default resolver (`RenderedBlocksResolver`) renders a model's default
block editor plus any `content_fields` you list in its registry entry. Swap
in your own when a model's real content lives somewhere neither of those
reaches — a WYSIWYG field outside the block editor, content assembled from
a relation, or a remote source.

```php
use TwillSeo\Contracts\ResolvedContent;
use TwillSeo\Contracts\SeoContentResolver;

class FaqPageContentResolver implements SeoContentResolver
{
    public function __construct(private readonly FaqRepository $faqs) {}

    public function resolve(object $model, string $locale): ResolvedContent
    {
        $html = $this->faqs->forPage($model, $locale)
            ->map(fn (Faq $faq) => "<h2>{$faq->question}</h2><p>{$faq->answer}</p>")
            ->implode('');

        return new ResolvedContent($html, content_source: 'faq_relation');
    }
}
```

```php
// config/twill-seo.php
'models' => [
    'faq_pages' => [
        'model' => App\Models\FaqPage::class,
        'content' => App\Analysis\FaqPageContentResolver::class,
    ],
],
```

Resolved through the container, so a resolver can declare its own
dependencies (as above) exactly like a registry `url` invokable can.
`content_source` is opaque to the engine — it only ever reaches the analyze
endpoint's `meta.content_source` field, for a host or the editor panel to
display if useful.

## A custom schema `GraphPiece`

Every built-in node (`Organization`/`Person`, `WebSite`, `WebPage`,
`Article`, `BreadcrumbList`, the shared primary `ImageObject`) is a
`GraphPiece`. Add your own the same way — a `Product`, a `FAQPage`'s
`Question`/`Answer` entities, a `Review` — either registered for every page
via config, or pushed for one page at request time.

```php
use TwillSeo\Contracts\GraphPiece;
use TwillSeo\Services\Schema\SchemaContext;
use TwillSeo\Services\Schema\SchemaIds;

final class ProductPiece implements GraphPiece
{
    public function pieces(SchemaContext $context): array
    {
        $model = $context->pageSeo->model;

        if (! $model instanceof Product) {
            return []; // not this page's type — contribute nothing, never throw
        }

        return [[
            '@type' => 'Product',
            '@id' => SchemaIds::pageUrl($context).'#product',
            'name' => $model->name,
            'offers' => ['@type' => 'Offer', 'price' => (string) $model->price, 'priceCurrency' => 'EUR'],
        ]];
    }
}
```

Registered for every request via config:

```php
// config/twill-seo.php
'schema' => [
    'pieces' => [App\Seo\ProductPiece::class],
],
```

Or pushed for one request only, e.g. from a controller before the head
renders:

```php
use TwillSeo\Facades\TwillSeo;

TwillSeo::graph()->push(new ProductPiece);
// or push a class-string to resolve it through the container instead:
TwillSeo::graph()->push(ProductPiece::class);
```

Cross-reference other nodes by `@id` only (see `SchemaIds`) — never nest one
node's full body inside another. `SchemaContext` carries everything a piece
needs (`$context->pageSeo`, `$context->settings`, `$context->siteUrl`,
`$context->locale`); a piece never queries or resolves anything itself
beyond what that context already hands it.

## A `BuildingSchemaGraph` listener

The last chance to mutate the assembled graph before it is serialized —
after every built-in piece, every `schema.pieces` entry and everything
pushed via `TwillSeo::graph()->push()` has already contributed. Useful for
a one-off edit that does not warrant a whole `GraphPiece` class, or for
removing/editing a node another piece already added.

```php
use Illuminate\Support\Facades\Event;
use TwillSeo\Events\BuildingSchemaGraph;

Event::listen(BuildingSchemaGraph::class, function (BuildingSchemaGraph $event): void {
    // $event->graph is a plain, non-readonly property — push a node:
    $event->graph[] = ['@type' => 'Organization', '@id' => '#legal-entity', 'name' => 'Acme Legal BV'];

    // ...or reassign it to edit/remove an existing one:
    $event->graph = array_values(array_filter(
        $event->graph,
        fn (array $node) => ($node['@type'] ?? null) !== 'BreadcrumbList',
    ));

    // $event->context is read-only: a listener may read the resolved
    // PageSeo/settings/site URL/locale, but changing "what page this is"
    // mid-build would leave the graph inconsistent with the meta tags
    // already rendered around it.
});
```

## `TwillSeo::page()` for a route with no model

Search results, a static contact page, a 404 — anything the head component
cannot resolve from a Twill model. Call it before `<x-twill-seo::head />`
renders (a controller method is the natural place):

```php
use TwillSeo\Facades\TwillSeo;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        TwillSeo::page(
            title: "Search results for \"{$request->string('q')}\"",
            description: 'Find what you need.',
            url: $request->fullUrl(),
            noindex: $request->string('q')->isEmpty(),
        );

        return view('search.index', /* ... */);
    }
}
```

`title` runs through the same template engine a model's own `{title}`
variable would (there is no per-entry `seo_title` to short-circuit it for a
manual page), so every `TwillSeo::page()` call gets the site's default title
template applied for consistent branding — pass the Head component's own
`$title` prop for a genuinely verbatim, un-templated title instead. With no
`breadcrumbs` array given, it defaults to Home → the given title, the same
default a registered model falls back to.

## The form sidebar chip

`SeoFields::fieldset()` already includes the full analysis panel. For a
compact, server-rendered (no JS) per-locale score summary somewhere else in
your own form layout — a sidebar, a custom summary block — use
`SeoFields::sideChip()` on its own:

```php
$form->addField(SeoFields::sideChip());
```

It reads whatever `ScoreCache` last wrote; it never runs the engine live.

## Per-type templates without the settings UI

The settings admin page (`{admin}/seo`) is the normal way to set a
content type's title/description templates, schema type and sitemap
inclusion — but every one of those values also has a `config/twill-seo.php`
default a host can set in code (`title.default_template`,
`schema.pieces`, and the registry entry's own `schema_type` /
`sitemap` keys), which the settings row overrides at runtime rather than
replaces. A host that wants a template fixed in code (never editable from
the admin) has no built-in way to lock that — the settings row always wins
once anything has been saved there for that content type.

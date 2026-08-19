# The schema.org graph

`<x-twill-seo::head />` renders one `<script type="application/ld+json">`
containing a flat `@graph`: every node is a sibling, and nodes reference
each other only by `@id` — never nested inside one another. `SchemaBuilder`
assembles it in a fixed order (see `src/Services/Schema/SchemaBuilder.php`):

1. The seven built-in pieces, below, in the order listed.
2. Every class named in `config('twill-seo.schema.pieces')`.
3. Every piece pushed onto `TwillSeo::graph()->push()` before the head
   renders.
4. A `BuildingSchemaGraph` event, giving listeners the last word.

Rendering is skipped entirely (not just hidden by the view) when there is
no page context or the `schema` feature is off, and the whole `<script>` tag
is omitted when the assembled graph turns out empty. Every `@id` is computed
by `TwillSeo\Services\Schema\SchemaIds`, the one place that scheme lives —
see its own doc comment for why every piece has to agree on it byte for
byte.

## The built-in nodes

### Organization or Person

Exactly one of the two ever contributes a node — whichever
`SeoSettings::entityType()` resolves to (`organization` by default). Both
carry `@id` = `{site}#organization` or `{site}#person`, `name`
(`SeoSettings::entityName()`), `url` (the site root), an optional `logo`
(Organization) or `image` (Person) `ImageObject` built from the settings
row's logo media id, and an optional `sameAs` array of the configured
social profile URLs.

### WebSite

Always contributes exactly one node — the site itself, never any one page.
`@id` = `{site}#website`, `name` = `SeoSettings::siteName()`, `publisher`
references the Organization/Person node by `@id`. When the settings row's
`search_action_enabled` is on and a `search_url_template` is set, adds a
`potentialAction` (`SearchAction`) — the sitelinks search box — with a fixed
`query-input: "required name=search_term_string"` naming the
`{search_term_string}` placeholder inside the template as required input.

### WebPage

The base per-page node, always contributed regardless of schema type.
`@id` = `{page}#webpage` (`{page}` is the canonical URL, falling back to the
resolved URL — see `SchemaIds::pageUrl()`). Carries `url`, `name` (the
resolved title), `isPartOf` (references WebSite), `inLanguage`, and
optionally `datePublished`/`dateModified`, `primaryImageOfPage` (references
the shared `ImageObject`, when there is a share image) and `breadcrumb`
(references BreadcrumbList, when there are any crumbs).

### Article

An **extra** node alongside WebPage's own — contributed only when the
resolved schema type is Article-ish (`Article`, or ends in `Article` or
`Posting` — `PageSeo::isArticleType()`, the single implementation of that
pattern match). `@id` = `{page}#article`, `@type` is the page's actual
schema type (so `BlogPosting`/`NewsArticle`/etc. are preserved, not
collapsed to `Article`), `headline` (the title, truncated to 110
characters — schema.org's own recommended ceiling), `isPartOf` and
`mainEntityOfPage` both reference WebPage, `publisher` references the
Organization/Person node, and optionally `datePublished`/`dateModified` and
an `image` reference to the shared `ImageObject`.

### BreadcrumbList

Contributed only when `PageSeo::breadcrumbs` is non-empty — the registry's
own `breadcrumbs` callback result, or the default Home → current-page pair.
Pure rendering: every fallback decision already happened in `SeoResolver`
(see its own `resolveBreadcrumbs()`/`defaultBreadcrumbs()`), so this piece
just turns the already-resolved `[title, url]` pairs into a `ListItem`
sequence. The last crumb (the current page) carries no `item` key at all —
schema.org's own convention for "this is where you are", not an
omitted-but-implied self-link.

### The shared primary `ImageObject`

Contributed only when the resolved page has a share image (the same
cascade `og:image` uses — entry's dedicated OG role → registry `image_role`
→ settings-wide default share image). `@id` = `{page}#primaryimage`.
WebPage's `primaryImageOfPage` and Article's `image` both reference this
**one** node by `@id` rather than each embedding its own copy of the same
image data — the reason this piece exists as its own node instead of being
inlined into WebPage/Article directly.

## The two extension points

- **`config('twill-seo.schema.pieces')`** — a list of `GraphPiece`
  class-strings, resolved through the container and included on every
  request, right after the seven built-ins.
- **`TwillSeo::graph()->push($piece)`** — a `GraphPiece` instance or
  class-string, pushed for the current request only (e.g. from a
  controller, before the head renders). `SchemaBuilder` is a per-request
  singleton and is never reset between calls within one request, so
  whatever a host pushes survives however many times `<x-twill-seo::head />`
  itself resolves a model internally.
- **The `BuildingSchemaGraph` event** — dispatched once, after every piece
  above has already contributed, with a mutable `$event->graph` array
  (push, edit or remove nodes in place) and a read-only `$event->context`.

Full worked examples of both — a `Product` piece, and a listener that edits
an already-built graph — are in [`docs/integration.md`](integration.md).

## Escaping

The graph is serialized with `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
| JSON_HEX_TAG` (see `resources/views/head.blade.php`): unescaped slashes
and unicode keep URLs and non-ASCII text readable, and `JSON_HEX_TAG` closes
off a `</script>` breakout inside any string value — a title containing a
literal `<script>` tag cannot terminate the block early.

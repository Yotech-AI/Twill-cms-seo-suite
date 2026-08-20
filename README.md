# Twill CMS SEO Suite

A Yoast-style SEO suite for [Twill CMS](https://twillcms.com): clean-room content analysis with traffic lights, meta tags, Open Graph and Twitter Cards, schema.org structured data, and XML sitemaps — plus an admin settings page to configure all of it.

**Requires PHP 8.3+, Laravel 12 or 13, Twill 3.6+.**

## Installation

```bash
composer require yotech-ai/twill-cms-seo-suite
```

If the package is not (yet) available to you through [Packagist](https://packagist.org), point Composer at the GitHub repository directly by adding a `repositories` entry to your project's `composer.json` and requiring `yotech-ai/twill-cms-seo-suite:^1.0`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Yotech-AI/Twill-cms-seo-suite"
        }
    ]
}
```

The package is fully self-contained — the shared Plugins-page code is vendored in (see below), so there is no second package to install or path-repository to configure.

```bash
php artisan twill-seo:install && php artisan migrate
```

`twill-seo:install` publishes `config/twill-seo.php` and prints the remaining steps, reproduced here for the model you want managed:

```php
// app/Models/Article.php
use TwillSeo\Models\Behaviors\HasSeo;

class Article extends Model
{
    use HasBlocks, HasMedias, HasSeo, HasSlug, HasTranslation;
}
```

```php
// app/Repositories/ArticleRepository.php
use TwillSeo\Repositories\Behaviors\HandleSeo;

class ArticleRepository extends ModuleRepository
{
    // HandleSeo must come AFTER HandleTranslations: HandleTranslations
    // rebuilds $fields['translations'] from scratch, which would wipe out
    // anything HandleSeo already injected there if it ran first.
    use HandleBlocks, HandleMedias, HandleSlugs, HandleTranslations, HandleSeo;
}
```

```php
// config/twill-seo.php
'models' => [
    'articles' => [
        'model' => App\Models\Article::class,
        'title_attribute' => 'title',
        'schema_type' => 'Article',
    ],
],
```

Then add `TwillSeo\Services\Form\SeoFields::fieldset()` to the module's form fields, and `<x-twill-seo::head />` to your public layout's `<head>`:

```php
// app/Http/Controllers/Twill/ArticleController.php
public function getForm(TwillModelContract $model): Form
{
    $form = parent::getForm($model);
    $form->addFieldset(SeoFields::fieldset());

    return $form;
}
```

```blade
{{-- resources/views/layouts/app.blade.php --}}
<x-twill-seo::head :model="$article ?? null" />
```

That's it: the admin gets a full SEO fieldset (keyphrase, title, description, social fields, and — when the analysis feature is on — a live traffic-light panel), and the front end gets a title, meta description, canonical link, robots meta, Open Graph, Twitter Cards and a JSON-LD `@graph`, all resolved through one cascade (DB row → per-type template → config default).

Visit `{admin}/seo` (reachable from the **Plugins** page card; set `twill-seo.ui.navigation_link` to `true` for an optional top-level nav entry) to configure site-wide settings, or run `php artisan twill-seo:doctor` at any time to check the install.

## Features

- **Content analysis.** A clean-room Yoast-equivalent engine (English, Dutch and German) scores SEO and readability out of 100, with per-check traffic lights and human-readable feedback. Runs live as an editor types (debounced, via `POST {admin}/seo/analyze`) and is cached on save so listing columns never re-run it.
- **Meta tags.** Title (templated per content type, or a verbatim `seo_title`), meta description, canonical URL, robots directives and hreflang alternates — one resolver (`SeoResolver`) is the single authority for every fallback decision.
- **Open Graph & Twitter Cards.** `og:*` and `twitter:*` tags with an image cascade (per-entry share image → registry role → site-wide default), each independently switchable.
- **Schema.org (JSON-LD).** A flat `@graph` — Organization/Person, WebSite, WebPage, Article (when applicable), BreadcrumbList and a shared primary image node — with two extension points for a host's own node types. See [`docs/schema.md`](docs/schema.md).
- **XML sitemap.** `/sitemap.xml` plus paginated per-type pages, image and hreflang entries, cached and invalidated on save/delete.
- **Settings admin page.** Site identity (name, tagline, separator, schema.org entity, logo, default share image, social profiles), per-content-type templates and sitemap toggles, feature switches, and advanced options (robots defaults, the sitelinks search box, uninstall behavior) — all editable at `{admin}/seo` without touching config.

## Listing columns

`TwillSeo\Services\Listings\SeoScoreColumn` and `ReadabilityScoreColumn` add a traffic-light dot to a module's index table, reading the score `ScoreCache` wrote on the model's last save (never running the engine live). Both hooks below live on the module's **controller**, not its repository — `additionalIndexTableColumns()` and `eagerLoadListingRelations()` are `A17\Twill\Http\Controllers\Admin\ModuleController` methods (verified against the vendored `area17/twill` 3.6 source):

```php
// app/Http/Controllers/Twill/ArticleController.php
use A17\Twill\Services\Listings\TableColumns;
use TwillSeo\Services\Listings\ReadabilityScoreColumn;
use TwillSeo\Services\Listings\SeoScoreColumn;

class ArticleController extends ModuleController
{
    protected function setUpController(): void
    {
        // Each cell reads $model->seo(app()->getLocale()), which lazy-loads
        // a seoEntry relation per row unless eager-loaded here.
        $this->eagerLoadListingRelations(['seoEntry.translations']);
    }

    protected function additionalIndexTableColumns(): TableColumns
    {
        return TableColumns::make()
            ->add(SeoScoreColumn::make())
            ->add(ReadabilityScoreColumn::make());
    }
}
```

See [`docs/integration.md`](docs/integration.md) for these two hooks in more detail.

## Config

`config/twill-seo.php`, published by `twill-seo:install`:

| Key | Meaning |
|---|---|
| `enabled` | Master switch (`TWILL_SEO_ENABLED`). When `false`, only the Plugins-page manifest registers — routes, views and migrations do not load. |
| `models` | The closed vocabulary of managed content types, keyed by a stable string never exposed as a class name to the client. See the table below. |
| `features.*` | Config-level defaults for the six feature toggles (`analysis`, `sitemap`, `schema`, `og`, `twitter`, `hreflang`) — the settings row overrides these at runtime. |
| `title.default_template` / `title.separator` | Fallback title template and separator when nothing more specific is configured. |
| `general.*` | Config-level defaults for the settings admin's General section (tagline, schema.org entity, logo/share-image media ids, social profiles) — `site_name` has no key here, it falls back to `config('app.name')` directly. |
| `robots.default_directives` | Directives appended after `index/noindex, follow/nofollow` on every robots meta tag. |
| `sitemap.path` / `sitemap.per_page` / `sitemap.cache_ttl` | Sitemap URL path, entries per paginated file, and cache TTL in seconds. |
| `schema.pieces` | Extra `GraphPiece` classes to include in the JSON-LD graph — see [`docs/schema.md`](docs/schema.md). |
| `schema.search_action_enabled` / `schema.search_url_template` | Config-level defaults for the sitelinks search box — the settings row overrides these at runtime. |
| `analysis.refresh_scores_on_save` / `analysis.debounce_ms` / `analysis.throttle` | Whether a save re-scores the item, the editor panel's debounce, and the analyze endpoint's rate limit. |
| `settings.throttle` | Rate limit for the settings admin's `PUT {admin}/seo/settings` endpoint. |

Each entry in `models` accepts:

| Key | Meaning |
|---|---|
| `model` | The Eloquent/Twill model class. Required. |
| `title_attribute` | Attribute used as `{title}` in templates. Default `title`. |
| `schema_type` | Default schema.org `@type` for this content type (overridable per type in the settings admin). Default `WebPage`. |
| `sitemap` / `sitemap_images` | Whether the type appears in the XML sitemap, and whether its entries include `<image:image>`. |
| `image_role` | Twill media role used as the Open Graph/Twitter/sitemap image when the entry has no dedicated `twill_seo_og_image`. |
| `url` | `fn (Model $model, string $locale): ?string`, a `Class::method` string, or an invokable class-string — overrides the default `getFullUrl()`/`SeoLinkable` cascade. See [`docs/integration.md`](docs/integration.md). |
| `content` | A `SeoContentResolver` class-string overriding the default (render the item's blocks + `content_fields`) for analysis. |
| `content_fields` | Translated attribute names appended to the analyzed content alongside rendered blocks. |
| `breadcrumbs` | `fn (Model $model, string $locale): list<array{0: string, 1: ?string}>` overriding the default Home → current-page breadcrumb. |

## Commands

| Command | Purpose |
|---|---|
| `twill-seo:install` | Publish the config and report the remaining setup steps. |
| `twill-seo:doctor` | Diagnose the install: plugin-page wiring, config, Twill version, database tables, every registered model (class, traits, repository, translated-attribute collisions, URL resolution), the settings row, hreflang locales, the sitemap route, built assets and a live engine smoke test. Table output; exits 1 on any failure, 0 with warnings otherwise. |

## Frontend assets

The built Vue apps (the editor panel and the settings admin) ship in `resources/dist` and are served from a package route with an ETag and a far-future cache header, so an upgrade can never leave a host running a stale copy. `php artisan vendor:publish --tag=twill-seo-assets` is optional; both routes prefer a published copy when one exists.

To rebuild from source: `npm install && npm run build`.

## The Plugins page

The shared Plugins-page code ships built in — no separate dependency required. It adds a **Plugins** entry to the admin navigation (next to Media Library) listing every installed Yotech plugin, with a link to each plugin's own admin screen. Nothing to configure.

### How it works

- Shared state lives in the Laravel container under two well-known keys: `yotech.twill-plugins.registry` (an `ArrayObject` of plugin manifests, plain arrays only) and `yotech.twill-plugins.page-owner` (the provider class that owns the page).
- The **first** Yotech plugin provider to register binds both keys, registers the `plugins` admin route/controller/view, and owns the page.
- Every **later** Yotech plugin provider — even one vendoring a differently-namespaced copy of this same code, as this package does — detects the existing bindings and only adds its own manifest to the registry, so an install with several Yotech plugins still shows a single Plugins page listing all of them.

## Further reading

- [`docs/integration.md`](docs/integration.md) — advanced recipes: `SeoLinkable`, a custom `SeoContentResolver`, a custom schema `GraphPiece` and `BuildingSchemaGraph` listener, `TwillSeo::page()` for non-model routes, the form sidebar chip, listing columns, per-type templates, PUT/settings-row semantics.
- [`docs/analysis.md`](docs/analysis.md) — how scoring works, and every place this engine deliberately judges a text differently from the analysis it takes its thresholds from.
- [`docs/schema.md`](docs/schema.md) — the emitted JSON-LD graph, node by node, and both extension points.
- [`docs/lang-data-sources.md`](docs/lang-data-sources.md) — where every word list came from, and why (MIT clean-room: nothing here is copied from Yoast).

## License

MIT

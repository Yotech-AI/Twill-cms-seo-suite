<?php

namespace TwillSeo\Services\Sitemap;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use TwillSeo\Services\ModelRegistry;
use TwillSeo\Services\Resolvers\UrlResolver;
use TwillSeo\Services\Settings\SeoSettings;
use TwillSeo\Support\TwillMedia;
use XMLWriter;

/**
 * Builds the two XML documents SitemapController serves: one urlset per
 * registered type/page (render()) and the sitemapindex that links them all
 * together (renderIndex()). Neither method touches the cache layer —
 * SitemapCache wraps both from the controller, so this class is just "given
 * a type/page, produce the document" with no knowledge of caching at all.
 *
 * Eligibility (published() + visible(), when the model has them — see
 * eligibleQuery()'s own doc comment — plus not robots_noindex) is decided
 * entirely in the database query (eligibleQuery()); a resolvable URL is a
 * SEPARATE, per-model filter applied while rendering, because UrlResolver
 * can run an arbitrary host callback that no SQL query could express. That
 * split means pageCount()/the index's per-type page count are computed from
 * the DB-eligible set only — a model whose URL happens to resolve to null is
 * still "on" some page by count, it is simply skipped when that page
 * actually renders (see render()'s own loop). This is the same tradeoff
 * Yoast itself accepts for URL-less posts and keeps pageCount() a single
 * cheap COUNT(*) query independent of resolving every row's URL up front.
 */
final class SitemapBuilder
{
    /**
     * Duplicates TwillSeo\Models\Behaviors\HasSeo::OG_IMAGE_ROLE's value —
     * PHP forbids reading a trait constant directly via the trait name from
     * outside a class that composes it, and $model here is typed `object`
     * with no concrete class known to read it through (see HasSeo's own doc
     * comment; SeoResolver and SeoFields hit the identical constraint and
     * resolve it the same way). Keep in sync if the role name ever changes.
     */
    private const OG_IMAGE_ROLE = 'twill_seo_og_image';

    private const SITEMAP_XMLNS = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    private const IMAGE_XMLNS = 'http://www.google.com/schemas/sitemap-image/1.1';

    private const XHTML_XMLNS = 'http://www.w3.org/1999/xhtml';

    public function __construct(
        private readonly ModelRegistry $registry,
        private readonly SeoSettings $settings,
        private readonly UrlResolver $urlResolver,
    ) {}

    /**
     * One page's worth of eligible models for $key, DB-ordered by primary
     * key so forPage() pagination is stable across calls. Deliberately does
     * NOT restrict select() to a fixed column list: a registry `url`
     * callback, a SeoLinkable::getSeoUrl(), or the default getFullUrl() ->
     * getSlug() cascade can each depend on arbitrary host-defined attributes
     * that cannot be known generically here, so trimming columns would risk
     * silently breaking URL resolution for real host configurations. The
     * actual memory/perf lever is bounding every query to one page's rows
     * via forPage() rather than ever loading a whole type's table at once.
     */
    public function entries(string $key, int $page): iterable
    {
        return $this->eligibleQuery($key)
            ->forPage(max($page, 1), $this->perPage())
            ->get();
    }

    public function pageCount(string $key): int
    {
        $total = $this->eligibleQuery($key)->count();

        return $total === 0 ? 0 : (int) ceil($total / $this->perPage());
    }

    /**
     * A urlset document for one page of $key: <loc> + <lastmod>, plus
     * optional <xhtml:link> hreflang alternates and an <image:image> block
     * per entry, gated by the hreflang feature and the type's own
     * sitemap_images toggle respectively. A model whose URL resolves to
     * null (see the class doc comment) is skipped entirely — it never gets
     * an empty/broken <url> block.
     */
    public function render(string $key, int $page): string
    {
        $config = $this->registry->get($key);
        $locale = app()->getLocale();
        $wantsImages = (bool) ($config['sitemap_images'] ?? false);
        $hreflangLocales = $this->hreflangLocales($locale);

        $writer = $this->newWriter();
        $writer->startElement('urlset');
        $writer->writeAttribute('xmlns', self::SITEMAP_XMLNS);
        $writer->writeAttribute('xmlns:image', self::IMAGE_XMLNS);
        $writer->writeAttribute('xmlns:xhtml', self::XHTML_XMLNS);

        foreach ($this->entries($key, $page) as $model) {
            $loc = $this->urlResolver->resolve($model, $locale);

            if ($loc === null) {
                continue;
            }

            $writer->startElement('url');
            $writer->writeElement('loc', $loc);

            $lastmod = $this->w3cDate($model->updated_at ?? null);

            if ($lastmod !== null) {
                $writer->writeElement('lastmod', $lastmod);
            }

            if ($hreflangLocales !== []) {
                $this->writeAlternates($writer, $model, $hreflangLocales, $locale, $loc);
            }

            if ($wantsImages) {
                $this->writeImage($writer, $model, $config);
            }

            $writer->endElement(); // url
        }

        $writer->endElement(); // urlset

        return $this->finish($writer);
    }

    /**
     * A sitemapindex listing one <sitemap> entry per (type, page) pair for
     * every registry type with sitemapEnabled() true and at least one page
     * — a type split across N pages by SitemapBuilder is listed as N
     * separate <sitemap> entries here, mirroring how a real multi-file
     * sitemap set is advertised. Every page of a type shares that type's
     * single max-updated_at lastmod (the brief's own wording: "the index's
     * per-sitemap lastmod = max updated_at for that type", not per page) —
     * one extra aggregate query per type rather than one per page.
     *
     * Each type is rendered inside its own try/catch: one misconfigured
     * registry entry (a bad `model` class, a query that fails for a reason
     * eligibleQuery()'s own guards don't cover, ...) must never take down
     * the shared index response for every OTHER, healthy type. Reported and
     * skipped, exactly like ScoreCache/SitemapCache's own never-break
     * guards elsewhere in this package — just applied per loop iteration
     * here instead of around one call.
     */
    public function renderIndex(): string
    {
        $writer = $this->newWriter();
        $writer->startElement('sitemapindex');
        $writer->writeAttribute('xmlns', self::SITEMAP_XMLNS);

        foreach (array_keys($this->registry->all()) as $key) {
            try {
                $this->writeIndexEntriesForType($writer, $key);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $writer->endElement(); // sitemapindex

        return $this->finish($writer);
    }

    private function writeIndexEntriesForType(XMLWriter $writer, string $key): void
    {
        if (! $this->settings->sitemapEnabled($key)) {
            return;
        }

        $pageCount = $this->pageCount($key);

        if ($pageCount < 1) {
            return;
        }

        $lastmod = $this->w3cDate($this->eligibleQuery($key)->max('updated_at'));

        for ($page = 1; $page <= $pageCount; $page++) {
            $writer->startElement('sitemap');
            $writer->writeElement('loc', route('twill-seo.sitemap.show', ['type' => $key, 'page' => $page]));

            if ($lastmod !== null) {
                $writer->writeElement('lastmod', $lastmod);
            }

            $writer->endElement(); // sitemap
        }
    }

    /**
     * published()/visible() are Twill's own local scopes
     * (A17\Twill\Models\Model::scopePublished()/scopeVisible()) — NOT part
     * of HasSeo or of bare Eloquent. HasSeo is explicitly documented to
     * support "any host Eloquent model, Twill module or not" (see its own
     * doc comment), so a registered model can legitimately compose HasSeo
     * directly onto a plain Eloquent model with neither scope. Calling an
     * undefined local scope raises an uncaught BadMethodCallException from
     * Eloquent\Builder::__call() — guarded here exactly like the seoEntry
     * check below, via method_exists() on the model CLASS (scopes are
     * conventionally named methods on the model, not the builder). A model
     * without Twill's publish/visibility concept has nothing to filter by
     * on that axis, so its rows are simply never excluded by it — not
     * treated as ineligible by default (see SitemapTest's PlainModel
     * regression test, which documents this choice).
     */
    private function eligibleQuery(string $key): Builder
    {
        $modelClass = $this->registry->modelClass($key);

        /** @var Model $model */
        $model = new $modelClass;

        $query = $modelClass::query();

        if (method_exists($modelClass, 'scopePublished')) {
            $query->published();
        }

        if (method_exists($modelClass, 'scopeVisible')) {
            $query->visible();
        }

        // Registered models are expected to use HasSeo (the whole package
        // assumes it elsewhere — ScoreCache, SeoResolver), but this guard
        // costs nothing and keeps a model without SEO storage from fataling
        // on an undefined relation instead of just skipping the exclusion.
        if (method_exists($modelClass, 'seoEntry')) {
            $query->whereDoesntHave('seoEntry', fn (Builder $q) => $q->where('robots_noindex', true));
        }

        return $query->orderBy($model->getQualifiedKeyName());
    }

    /**
     * @param  list<string>  $locales
     */
    private function writeAlternates(XMLWriter $writer, object $model, array $locales, string $primaryLocale, string $primaryUrl): void
    {
        $byLocale = [];

        foreach ($locales as $locale) {
            $url = $locale === $primaryLocale ? $primaryUrl : $this->urlResolver->resolve($model, $locale);

            if ($url !== null) {
                $byLocale[$locale] = $url;
            }
        }

        if (count($byLocale) < 2) {
            return;
        }

        foreach ($byLocale as $locale => $url) {
            $writer->startElement('xhtml:link');
            $writer->writeAttribute('rel', 'alternate');
            $writer->writeAttribute('hreflang', $locale);
            $writer->writeAttribute('href', $url);
            $writer->endElement();
        }
    }

    /**
     * @param  array<string,mixed>  $config
     */
    private function writeImage(XMLWriter $writer, object $model, array $config): void
    {
        $image = $this->resolveImage($model, $config);

        if ($image === null) {
            return;
        }

        $writer->startElement('image:image');
        $writer->writeElement('image:loc', $image['url']);
        $writer->endElement();
    }

    /**
     * The same OG-role-then-registry-role cascade SeoResolver::
     * resolveShareImage() uses for the head's og:image, MINUS its third step
     * (the settings-wide default share image): that fallback exists so a
     * head render never shows no image at all, but repeating the SAME
     * install-wide image on every entry of a large sitemap is not a per-page
     * image in any meaningful sense — Yoast's own image sitemap only lists
     * images actually attached to that specific piece of content.
     *
     * @param  array<string,mixed>  $config
     * @return ?array{url: string, width: int, height: int}
     */
    private function resolveImage(object $model, array $config): ?array
    {
        $fromOgRole = TwillMedia::fromRole($model, self::OG_IMAGE_ROLE);

        if ($fromOgRole !== null) {
            return $fromOgRole;
        }

        $registryRole = $config['image_role'] ?? null;

        if (is_string($registryRole) && $registryRole !== '') {
            return TwillMedia::fromRole($model, $registryRole);
        }

        return null;
    }

    /**
     * @return list<string> empty when the hreflang feature is off — the
     *                      caller treats an empty list as "skip alternates
     *                      entirely" without a second feature check.
     */
    private function hreflangLocales(string $primaryLocale): array
    {
        if (! $this->settings->feature('hreflang')) {
            return [];
        }

        return array_values(array_unique(array_map(
            strval(...),
            (array) config('translatable.locales', [$primaryLocale]),
        )));
    }

    private function newWriter(): XMLWriter
    {
        $writer = new XMLWriter;
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->startDocument('1.0', 'UTF-8');

        return $writer;
    }

    private function finish(XMLWriter $writer): string
    {
        $writer->endDocument();

        return $writer->outputMemory();
    }

    /**
     * W3C Datetime (sitemaps.org protocol's own required lastmod format).
     * DATE_W3C and DATE_ATOM are literally the same format string
     * ("Y-m-d\TH:i:sP") — this package's schema pieces use DATE_ATOM for the
     * identical value (see ArticlePiece/WebPagePiece); DATE_W3C is used here
     * instead purely because it names the exact vocabulary the sitemap
     * protocol spec itself uses.
     */
    private function w3cDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_W3C);
        }

        try {
            return (new \DateTimeImmutable((string) $value))->format(DATE_W3C);
        } catch (\Throwable) {
            return null;
        }
    }

    private function perPage(): int
    {
        return max(1, (int) config('twill-seo.sitemap.per_page', 1000));
    }
}

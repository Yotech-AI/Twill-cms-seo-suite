<?php

namespace TwillSeo\Services\Resolvers;

use Throwable;
use TwillSeo\Contracts\SeoLinkable;
use TwillSeo\Services\ModelRegistry;

/**
 * The single place a permalink for an SEO-managed model gets resolved,
 * extracted from what PaperFactory::resolvePermalink() used to do privately
 * (see its Task 7 doc comment, which named this extraction ahead of time).
 * PaperFactory now delegates here; every other Task 7 consumer that needs a
 * page's URL (canonical link, og:url, hreflang alternates) goes through the
 * exact same cascade, so "what is this page's URL" can never quietly
 * disagree between the analysis engine and the rendered head.
 *
 * Cascade, each step independently guarded so a throwing host callback or
 * model method degrades to the next step rather than losing the permalink
 * entirely (report()ed, never allowed to bubble — this runs on every render
 * of the public head, not just an admin request):
 *   1. The registry's own `url` entry — a Closure/`Class::method` callable
 *      (as PaperFactory already supported), OR (new in Task 7) a bare
 *      invokable class-string resolved through the container, so a host
 *      resolver can have its own dependencies exactly like a registry
 *      `content` resolver can.
 *   2. `SeoLinkable::getSeoUrl($locale)` when the model implements it — the
 *      one step that receives the REQUESTED locale rather than reading
 *      ambient state, see that contract's own doc comment.
 *   3. Twill's own `getFullUrl()` — note this reads `app()->getLocale()`
 *      internally and ignores $locale entirely (that is Twill's contract,
 *      not a bug here); a registry callback or SeoLinkable is what a host
 *      needs for correct multi-locale URLs (e.g. hreflang) through this step.
 *   4. null.
 *
 * Twill's own getFullUrl() returns the literal string '#' (not an exception)
 * when it cannot resolve a real permalink (no getSlug(), or no controller
 * wired for the model — see A17\Twill\Models\Model::getFullUrl()). That
 * sentinel is treated as "unresolved" here (→ null) rather than passed
 * through as if it were a real URL: a rendered `<link rel="canonical"
 * href="#">` would be a real bug for every new Task 7 consumer. Verified
 * this does not change PaperFactory's own observable behavior: the only
 * consumer of Paper::permalink today is TwillSeo\Analysis\Html\LinkScope::
 * forHref(), via parse_url($permalink, PHP_URL_HOST) — and
 * parse_url('#', PHP_URL_HOST) and parse_url('', PHP_URL_HOST) both return
 * null, so '#' and '' (PaperFactory's own ?? '' fallback for a null
 * resolve()) classify links identically either way.
 */
final class UrlResolver
{
    private const UNRESOLVED_SENTINEL = '#';

    public function __construct(private readonly ModelRegistry $registry) {}

    public function resolve(object $model, string $locale): ?string
    {
        $key = $this->registry->keyFor($model);
        $config = $key !== null ? $this->registry->get($key) : [];

        $viaCallback = $this->resolveViaRegistryCallback($config['url'] ?? null, $model, $locale);

        if ($viaCallback !== null) {
            return $viaCallback;
        }

        $viaLinkable = $this->resolveViaLinkable($model, $locale);

        if ($viaLinkable !== null) {
            return $viaLinkable;
        }

        return $this->resolveViaFullUrl($model);
    }

    private function resolveViaRegistryCallback(mixed $callback, object $model, string $locale): ?string
    {
        $resolved = $this->asCallable($callback);

        if ($resolved === null) {
            return null;
        }

        try {
            $url = $resolved($model, $locale);
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        return $this->nonEmpty($url === null ? null : (string) $url);
    }

    /**
     * Accepts everything PaperFactory's own is_callable() check already did
     * (Closures, "Class::method" strings, [$obj, 'method'] arrays) plus a
     * bare invokable class-string ("App\Foo::class", i.e. just the FQCN),
     * which is_callable() alone never recognizes for an uninstantiated
     * class — PHP only treats [ClassName, 'method'] as callable for a
     * STATIC method, and a plain class-name string is never itself callable
     * (verified empirically before writing this). Resolved through the
     * container so a host resolver can declare its own dependencies.
     */
    private function asCallable(mixed $callback): ?callable
    {
        if (is_callable($callback)) {
            return $callback;
        }

        if (is_string($callback) && $callback !== '' && class_exists($callback) && method_exists($callback, '__invoke')) {
            try {
                $instance = app($callback);
            } catch (Throwable $e) {
                report($e);

                return null;
            }

            return $instance instanceof \Closure || is_callable($instance) ? $instance : null;
        }

        return null;
    }

    private function resolveViaLinkable(object $model, string $locale): ?string
    {
        if (! $model instanceof SeoLinkable) {
            return null;
        }

        try {
            return $this->nonEmpty($model->getSeoUrl($locale));
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    private function resolveViaFullUrl(object $model): ?string
    {
        if (! method_exists($model, 'getFullUrl')) {
            return null;
        }

        try {
            $url = (string) $model->getFullUrl();
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        return $url === self::UNRESOLVED_SENTINEL ? null : $this->nonEmpty($url);
    }

    private function nonEmpty(?string $value): ?string
    {
        return $value !== null && $value !== '' ? $value : null;
    }
}

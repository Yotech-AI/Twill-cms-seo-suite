<?php

namespace TwillSeo\Services\Sitemap;

use Closure;
use Illuminate\Support\Facades\Cache;
use TwillSeo\Services\ModelRegistry;

/**
 * The single owner of the sitemap's cache key scheme. SitemapController is
 * the only reader (via rememberIndex()/rememberPage()); HandleSeo's
 * afterSaveHandleSeo tail and HasSeo's delete listener are the only writers
 * of the invalidating side (via forgetFor()) — see those two files for the
 * guarded call sites.
 *
 * Keys, exactly as the brief specifies:
 *   - twill-seo.sitemap.index            the sitemapindex document
 *   - twill-seo.sitemap.{type}.{page}    one rendered urlset document
 *
 * Invalidation never calls Cache::flush(): that would wipe the ENTIRE
 * application cache store, not just this package's documents, and is an
 * unacceptable blast radius for "one article got saved". A cache store has
 * no general "forget every key matching a prefix" primitive that works
 * across every driver (array/file included — no wildcard scan, and tags are
 * not universally supported), so forgetting "all of a type's cached pages"
 * requires knowing exactly which page numbers were ever cached. That is
 * tracked here as a small per-type "high-water mark" entry
 * (twill-seo.sitemap.{type}.page_count, an implementation detail with no
 * literal name in the brief) — the highest page number remembered since the
 * last flush — so flushType() knows exactly which page keys to forget.
 */
final class SitemapCache
{
    private const INDEX_KEY = 'twill-seo.sitemap.index';

    public function __construct(private readonly ModelRegistry $registry) {}

    public function rememberIndex(Closure $render): string
    {
        return Cache::remember(self::INDEX_KEY, $this->ttl(), $render);
    }

    public function rememberPage(string $key, int $page, Closure $render): string
    {
        $this->trackPage($key, $page);

        return Cache::remember($this->pageKey($key, $page), $this->ttl(), $render);
    }

    /**
     * Forgets every page key this type has cached since the last flush, plus
     * its own tracker key and the shared index (any type's content can
     * appear in the index, so any type's save/delete has to drop it too).
     */
    public function flushType(string $key): void
    {
        $tracked = (int) Cache::get($this->trackerKey($key), 0);

        for ($page = 1; $page <= $tracked; $page++) {
            Cache::forget($this->pageKey($key, $page));
        }

        Cache::forget($this->trackerKey($key));
        Cache::forget(self::INDEX_KEY);
    }

    public function flushAll(): void
    {
        foreach (array_keys($this->registry->all()) as $key) {
            $this->flushType($key);
        }

        Cache::forget(self::INDEX_KEY);
    }

    /**
     * Registry-lookup convenience for the save/delete invalidation hooks: a
     * no-op when $model is not one this package manages, exactly like
     * ScoreCache::refresh()'s own keyFor() guard. The hooks call this rather
     * than flushType() directly so neither HandleSeo nor HasSeo needs to know
     * the registry lookup at all — one less thing their guarded try/catch
     * tail has to get right.
     */
    public function forgetFor(object $model): void
    {
        $key = $this->registry->keyFor($model);

        if ($key !== null) {
            $this->flushType($key);
        }
    }

    /**
     * Raises the tracked high-water mark for $key when $page is new,
     * refreshing its TTL so it keeps covering every page still alive in the
     * cache. Never lowers the mark: a page rendered earlier under a since-
     * shrunk result set must still get forgotten by flushType(), not left
     * orphaned because the mark briefly dipped below it.
     */
    private function trackPage(string $key, int $page): void
    {
        $current = (int) Cache::get($this->trackerKey($key), 0);

        if ($page > $current) {
            Cache::put($this->trackerKey($key), $page, $this->ttl());
        }
    }

    private function pageKey(string $key, int $page): string
    {
        return "twill-seo.sitemap.{$key}.{$page}";
    }

    private function trackerKey(string $key): string
    {
        return "twill-seo.sitemap.{$key}.page_count";
    }

    private function ttl(): int
    {
        return (int) config('twill-seo.sitemap.cache_ttl', 3600);
    }
}

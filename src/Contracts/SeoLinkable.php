<?php

namespace TwillSeo\Contracts;

/**
 * An opt-in escape hatch for a host model that needs full control over its
 * own per-locale URL — e.g. a model whose public route lives outside Twill's
 * own controller/slug machinery entirely, where `getFullUrl()` either does
 * not apply or (unlike a registry `url` callback) the host would rather keep
 * the logic on the model itself.
 *
 * UrlResolver checks for this contract AFTER the registry's own `url`
 * callback (which always wins when configured) and BEFORE falling back to
 * Twill's `getFullUrl()`. Unlike `getFullUrl()` — which reads the ambient
 * `app()->getLocale()` and ignores whatever locale a caller actually wants —
 * `getSeoUrl()` takes the target locale explicitly, so it is the one path
 * here that can produce genuinely correct per-locale URLs for hreflang
 * without a registry callback.
 */
interface SeoLinkable
{
    /**
     * Null means "no URL for this locale" (e.g. an untranslated locale) —
     * UrlResolver treats that exactly like any other unresolved step and
     * keeps falling through the cascade, it never throws.
     */
    public function getSeoUrl(string $locale): ?string;
}

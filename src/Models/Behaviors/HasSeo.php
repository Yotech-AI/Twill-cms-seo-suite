<?php

namespace TwillSeo\Models\Behaviors;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Throwable;
use TwillSeo\Models\SeoEntry;
use TwillSeo\Models\SeoEntryTranslation;
use TwillSeo\Services\Sitemap\SitemapCache;

/**
 * Attaches Twill SEO storage to any host Eloquent model, Twill module or not
 * (see the Page fixture). `initializeHasSeo()`/`bootHasSeo()` are Laravel's
 * own per-instance/per-class trait hooks (Model::bootTraits()), separate from
 * HandleSeo's Twill-specific traitsMethods() dispatch, which only runs on the
 * repository side.
 */
trait HasSeo
{
    /**
     * Twill media role for the Open Graph / Twitter share image. Package-
     * owned and namespaced so it can never collide with a host's own roles.
     *
     * Note: PHP forbids reading a trait constant directly via the trait name
     * from outside a class that uses the trait ("Cannot access trait
     * constant ... directly") — only self:: (from within the trait) and
     * HostModel::OG_IMAGE_ROLE (via a class composing it) work. SeoFields,
     * which has no host model in scope, duplicates this literal with a
     * comment pointing back here; keep the two in sync.
     */
    public const OG_IMAGE_ROLE = 'twill_seo_og_image';

    /**
     * Declared here (not left to the host model) so initializeHasSeo()'s
     * assignment below lands on a real PHP property. Without a declaration,
     * Eloquent's __set() magic would treat "mediasParams" as an undefined
     * *attribute* instead — silently corrupting the model's own INSERT/UPDATE
     * with a bogus array-valued column.
     */
    public $mediasParams;

    public function seoEntry(): MorphOne
    {
        return $this->morphOne(SeoEntry::class, 'seoable');
    }

    /**
     * No implicit locale fallback: a missing translation for the requested
     * locale returns null rather than silently substituting another
     * locale's copy, so callers (head rendering, analysis) can't ship SEO
     * text in the wrong language.
     */
    public function seo(?string $locale = null): ?SeoEntryTranslation
    {
        return $this->seoEntry?->translation($locale ?? app()->getLocale());
    }

    /**
     * Registers the OG/Twitter share image role without discarding whatever
     * crops the host model already declares. Only models using HasMedias
     * have a mediasParams contract to extend.
     */
    public function initializeHasSeo(): void
    {
        if (! method_exists($this, 'getMediasParams')) {
            return;
        }

        // Mirrors HasMedias::getMediasParams()'s own fallback: an
        // uncustomized model resolves entirely from config, so seed the
        // merge from that same base rather than losing the host's default
        // crops the moment we add our own role.
        $params = (isset($this->mediasParams) && is_array($this->mediasParams))
            ? $this->mediasParams
            : (array) config('twill.default_crops');

        $params[self::OG_IMAGE_ROLE] ??= [
            'default' => [
                ['name' => 'default', 'ratio' => 1.91],
            ],
        ];

        $this->mediasParams = $params;
    }

    public static function bootHasSeo(): void
    {
        static::deleted(static function ($model): void {
            // Soft deletes keep SEO data around for restore; only a real row
            // removal takes the SeoEntry (and, via FK cascade, its
            // translations) with it. Mirrors HasMedias::bootHasMedias().
            if (! method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                $model->seoEntry()->delete();
            }
        });

        // A separate listener (not folded into the one above): this one must
        // fire on EVERY delete, soft or force, unlike the isForceDeleting()
        // gate above. A soft-deleted row drops out of the default (non-
        // trashed) Eloquent query SitemapBuilder queries immediately —
        // without this, a sitemap page cached before the delete would keep
        // listing it as a real <url> until the cache's TTL expired on its
        // own. Never allowed to break a delete — wrapped exactly like the
        // ScoreCache/SitemapCache calls in HandleSeo::afterSaveHandleSeo.
        static::deleted(static function ($model): void {
            try {
                app(SitemapCache::class)->forgetFor($model);
            } catch (Throwable $e) {
                report($e);
            }
        });
    }
}

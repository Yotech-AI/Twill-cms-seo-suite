<?php

namespace TwillSeo\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One row per SEO-managed host record (see HandleSeo, which is the only
 * writer). Not a Twill module — a plain Eloquent model owned entirely by
 * this package.
 *
 * @property int $id
 * @property string $seoable_type
 * @property int $seoable_id
 * @property bool $cornerstone
 * @property bool $robots_noindex
 * @property bool $robots_nofollow
 * @property string|null $schema_type_override
 * @property Collection<int, SeoEntryTranslation> $translations
 */
class SeoEntry extends Model
{
    protected $table = 'twill_seo_entries';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'cornerstone' => 'boolean',
            'robots_noindex' => 'boolean',
            'robots_nofollow' => 'boolean',
        ];
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function translations(): HasMany
    {
        return $this->hasMany(SeoEntryTranslation::class, 'twill_seo_entry_id');
    }

    public function translation(string $locale): ?SeoEntryTranslation
    {
        return $this->translations->firstWhere('locale', $locale);
    }

    /**
     * Returns the existing translation for $locale, or a new (unsaved) one
     * pushed into the already-loaded translations collection so a second
     * call for the same locale — likely later in the same save, since a
     * request can touch several seo_* fields sharing a locale — returns
     * this exact instance instead of silently creating a second, discarded
     * one before either is saved.
     */
    public function translationOrNew(string $locale): SeoEntryTranslation
    {
        if ($existing = $this->translation($locale)) {
            return $existing;
        }

        $translation = $this->translations()->make(['locale' => $locale]);

        $this->translations->push($translation);

        return $translation;
    }
}

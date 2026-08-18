<?php

namespace TwillSeo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $twill_seo_entry_id
 * @property string $locale
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property string|null $focus_keyphrase
 * @property string|null $canonical_url
 * @property string|null $og_title
 * @property string|null $og_description
 * @property string|null $twitter_title
 * @property string|null $twitter_description
 * @property int|null $seo_score
 * @property int|null $readability_score
 * @property array|null $analysis_summary
 * @property Carbon|null $analyzed_at
 */
class SeoEntryTranslation extends Model
{
    protected $table = 'twill_seo_entry_translations';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'analysis_summary' => 'array',
            'analyzed_at' => 'datetime',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(SeoEntry::class, 'twill_seo_entry_id');
    }
}

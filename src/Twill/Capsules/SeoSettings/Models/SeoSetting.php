<?php

namespace TwillSeo\Twill\Capsules\SeoSettings\Models;

use A17\Twill\Models\Behaviors\HasFiles;
use A17\Twill\Models\Behaviors\HasMedias;
use A17\Twill\Models\Model;

/**
 * The settings singleton, as a real Twill model so the settings screen is a
 * native Twill form: full content width, native fields, and the built-in
 * libraries for the images. The logo attaches through Twill's FILE library
 * (a logo is usually an SVG, which the media library's image pipeline
 * rejects/rasterizes); the default share image stays a media role. The
 * legacy media logo role and the even older bare-id general JSON keys both
 * remain readable fallbacks — see SeoSettings::logo().
 *
 * The four JSON columns are the same storage the package has always used;
 * every SeoSettings accessor reads them unchanged.
 */
class SeoSetting extends Model
{
    use HasFiles;
    use HasMedias;

    public const LOGO_ROLE = 'logo';

    public const DEFAULT_SHARE_ROLE = 'default_share';

    /**
     * File role for the logo. Same string as the legacy media LOGO_ROLE on
     * purpose — files and medias pivot through different tables, so the two
     * never collide, and one "logo" name stays true everywhere.
     */
    public $filesParams = [self::LOGO_ROLE];

    protected $table = 'twill_seo_settings';

    protected $fillable = [
        'published',
        'general',
        'content_types',
        'features',
        'advanced',
    ];

    protected $casts = [
        'general' => 'array',
        'content_types' => 'array',
        'features' => 'array',
        'advanced' => 'array',
    ];

    public $mediasParams = [
        self::LOGO_ROLE => [
            'default' => [
                ['name' => 'default', 'ratio' => 1],
            ],
        ],
        self::DEFAULT_SHARE_ROLE => [
            'default' => [
                ['name' => 'default', 'ratio' => 1.91],
            ],
        ],
    ];

    /**
     * The edit screen's header label. The table has no title column — this
     * is a singleton settings record, not content — so without an accessor
     * Twill renders a red "Missing title" placeholder. The title-editor
     * pencil still shows but edits are not persisted (title is not
     * fillable), matching how title-less Twill singletons behave.
     */
    public function getTitleAttribute(): string
    {
        return __('SEO settings');
    }

    /**
     * The single settings row (id 1), created on demand — the shape the rest
     * of the package has depended on since the storage task.
     */
    public static function current(): self
    {
        return static::withoutGlobalScopes()->firstOrCreate(['id' => 1]);
    }

    /**
     * One morph identity for this row no matter which class touches it: the
     * backwards-compatible TwillSeo\Models\SeoSetting shim subclasses this
     * model, and without this pin the two classes would write different
     * mediable_type strings — media attached through one would be invisible
     * through the other. self::class resolves to THIS class even when
     * called on the shim.
     */
    public function getMorphClass()
    {
        return self::class;
    }
}

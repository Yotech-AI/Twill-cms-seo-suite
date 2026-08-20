<?php

namespace TwillSeo\Tests\Fixtures\Models;

use A17\Twill\Models\Behaviors\HasBlocks;
use A17\Twill\Models\Behaviors\HasMedias;
use A17\Twill\Models\Behaviors\HasSlug;
use A17\Twill\Models\Behaviors\HasTranslation;
use A17\Twill\Models\Model;
use TwillSeo\Models\Behaviors\HasSeo;

/**
 * A miniature real Twill module — translatable, sluggable, with blocks and
 * medias — so later tasks can exercise repository and SEO-trait behavior
 * without standing up a full host application.
 */
class Article extends Model
{
    use HasBlocks;
    use HasMedias;
    use HasSeo;
    use HasSlug;
    use HasTranslation;

    protected $table = 'articles';

    protected $fillable = [
        'published',
        'position',
    ];

    public $translatedAttributes = [
        'title',
        'description',
    ];

    public $slugAttributes = [
        'title',
    ];

    /**
     * Declared with the fixture's own role, the way real Twill models do it.
     * This declaration doubles as the composition regression for the
     * host-crash found in first host QA: HasSeo must NOT declare
     * $mediasParams itself, or composing this class fatals with
     * "definition differs and is considered incompatible".
     */
    public $mediasParams = [
        'cover' => [
            'default' => [
                ['name' => 'default', 'ratio' => 16 / 9],
            ],
        ],
    ];
}

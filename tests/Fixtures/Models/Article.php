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
}

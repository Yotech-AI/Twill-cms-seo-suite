<?php

namespace TwillSeo\Tests\Fixtures\Models;

use A17\Twill\Models\Behaviors\HasMedias;
use A17\Twill\Models\Model;
use TwillSeo\Models\Behaviors\HasSeo;

/**
 * A HasMedias host that never declares $mediasParams — the initializeHasSeo()
 * skip path. The OG role must NOT be merged for this shape: assigning to an
 * undeclared property would route through Eloquent's __set() and corrupt the
 * model's own INSERT/UPDATE. Reuses the articles table; never persisted.
 */
class ArticleWithoutDeclaredCrops extends Model
{
    use HasMedias;
    use HasSeo;

    protected $table = 'articles';

    protected $fillable = [
        'published',
    ];
}

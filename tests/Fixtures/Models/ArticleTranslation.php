<?php

namespace TwillSeo\Tests\Fixtures\Models;

use A17\Twill\Models\Model;

class ArticleTranslation extends Model
{
    protected $table = 'article_translations';

    protected $baseModuleModel = Article::class;

    protected $fillable = [
        'active',
        'locale',
        'title',
        'description',
    ];
}

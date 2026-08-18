<?php

namespace TwillSeo\Tests\Fixtures\Models;

use A17\Twill\Models\Model;
use TwillSeo\Models\Behaviors\HasSeo;

/**
 * The collision fixture: an UNTRANSLATED Twill module (no HasTranslation)
 * that happens to have a real `seo_title` column of its own. HandleSeoSaveTest
 * uses it to prove the package's locale-keyed seo_title FORM field never
 * reaches that column via fill(), even though the names are identical.
 */
class Page extends Model
{
    use HasSeo;

    protected $table = 'pages';

    protected $fillable = [
        'title',
        'published',
        'seo_title',
    ];
}

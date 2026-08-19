<?php

namespace TwillSeo\Tests\Fixtures\Models;

use A17\Twill\Models\Model;
use TwillSeo\Models\Behaviors\HasSeo;

/**
 * A doctor-only negative fixture (see DoctorCommandTest): declares seo_title
 * as one of its own translatedAttributes, colliding with the seo_* form
 * field names HandleSeo stashes-then-strips before HandleTranslations ever
 * sees $fields (see Repositories\Behaviors\HandleSeo's own doc comment).
 *
 * Reuses the `articles` table rather than a dedicated migration — this
 * fixture is instantiated and reflected on (translatedAttributes read as a
 * plain property), never actually saved through a translation pipeline, so
 * no schema of its own is needed. HasTranslation is deliberately NOT
 * composed here: doctor's check only reads the public property, and
 * composing the real trait would invite incidental Twill trait-hook
 * behavior (e.g. translation-model-name resolution) this fixture has no use
 * for and no matching *Translation class to satisfy.
 */
class BrokenTranslatedAttributesArticle extends Model
{
    use HasSeo;

    protected $table = 'articles';

    public $translatedAttributes = ['title', 'seo_title'];
}

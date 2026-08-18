<?php

namespace TwillSeo\Tests\Fixtures\Repositories;

use A17\Twill\Repositories\ModuleRepository;
use TwillSeo\Repositories\Behaviors\HandleSeo;
use TwillSeo\Tests\Fixtures\Models\Page;

/**
 * HandleSeo only — no translations/slugs/blocks/medias — so
 * HandleSeoSaveTest can exercise the seo_title collision and the
 * non-array (untranslated host) seo_* value path without any other
 * repository behavior in the way.
 */
class PageRepository extends ModuleRepository
{
    use HandleSeo;

    public function __construct(Page $model)
    {
        $this->model = $model;
    }
}

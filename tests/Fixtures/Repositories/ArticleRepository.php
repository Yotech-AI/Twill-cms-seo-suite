<?php

namespace TwillSeo\Tests\Fixtures\Repositories;

use A17\Twill\Repositories\Behaviors\HandleBlocks;
use A17\Twill\Repositories\Behaviors\HandleMedias;
use A17\Twill\Repositories\Behaviors\HandleSlugs;
use A17\Twill\Repositories\Behaviors\HandleTranslations;
use A17\Twill\Repositories\ModuleRepository;
use TwillSeo\Repositories\Behaviors\HandleSeo;
use TwillSeo\Tests\Fixtures\Models\Article;

class ArticleRepository extends ModuleRepository
{
    use HandleBlocks;
    use HandleMedias;
    use HandleSlugs;
    use HandleTranslations;

    // Deliberately NOT alphabetized with the traits above (pint.json disables
    // ordered_traits for this reason): must stay after HandleTranslations,
    // since its getFormFieldsHandleTranslations unsets and rebuilds
    // $fields['translations'] from scratch, which would wipe out anything
    // HandleSeo injected there if it ran first.
    use HandleSeo;

    public function __construct(Article $model)
    {
        $this->model = $model;
    }
}

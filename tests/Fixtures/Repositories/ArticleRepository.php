<?php

namespace TwillSeo\Tests\Fixtures\Repositories;

use A17\Twill\Repositories\Behaviors\HandleBlocks;
use A17\Twill\Repositories\Behaviors\HandleMedias;
use A17\Twill\Repositories\Behaviors\HandleSlugs;
use A17\Twill\Repositories\Behaviors\HandleTranslations;
use A17\Twill\Repositories\ModuleRepository;
use TwillSeo\Tests\Fixtures\Models\Article;

class ArticleRepository extends ModuleRepository
{
    use HandleBlocks;
    use HandleMedias;
    use HandleSlugs;
    use HandleTranslations;

    public function __construct(Article $model)
    {
        $this->model = $model;
    }
}

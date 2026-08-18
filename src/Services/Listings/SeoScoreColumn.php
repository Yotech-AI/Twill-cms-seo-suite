<?php

namespace TwillSeo\Services\Listings;

use A17\Twill\Models\Contracts\TwillModelContract;
use A17\Twill\Services\Listings\TableColumn;
use TwillSeo\Support\ScoreRating;

/**
 * The SEO score traffic light for a module's listing table. Reads the cached
 * score ScoreCache wrote on the last save — never runs the engine live, so a
 * listing page of a thousand rows costs nothing extra to render.
 */
final class SeoScoreColumn extends TableColumn
{
    public static function make(): static
    {
        $column = new self;

        $column->field('seo_score')
            ->title('SEO')
            ->optional()
            ->renderHtml()
            ->customRender(
                fn (TwillModelContract $model) => ScoreRating::dot($model->seo(app()->getLocale())?->seo_score)
            );

        return $column;
    }
}

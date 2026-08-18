<?php

namespace TwillSeo\Services\Form;

use A17\Twill\Services\Forms\Fields\Checkbox;
use A17\Twill\Services\Forms\Fields\Input;
use A17\Twill\Services\Forms\Fields\Medias;
use A17\Twill\Services\Forms\Fieldset;

/**
 * The seo_* form fields every managed model gets, as a single closed
 * fieldset a host drops into its module's edit view. Field names here are
 * the contract HandleSeo's mapping table reads $fields by — keep them in
 * sync with TwillSeo\Repositories\Behaviors\HandleSeo.
 */
class SeoFields
{
    /**
     * Duplicates TwillSeo\Models\Behaviors\HasSeo::OG_IMAGE_ROLE's value.
     * PHP forbids reading a trait constant directly via the trait name from
     * outside a class that uses it, and SeoFields (a static factory with no
     * host model in scope) has no such class to read it through. Keep this
     * in sync with HasSeo's copy if the role name ever changes.
     */
    private const OG_IMAGE_ROLE = 'twill_seo_og_image';

    /**
     * $social/$advanced let a host trim the fieldset down to just the
     * always-on keyphrase/title/description trio. No $analysis parameter
     * yet: a later task prepends the score/readability panel ahead of these
     * fields once the analysis engine exists.
     */
    public static function fieldset(bool $social = true, bool $advanced = true): Fieldset
    {
        $fields = [
            Input::make()->name('seo_keyphrase')->label(__('Focus keyphrase'))->translatable()
                ->note(__('The main term or phrase you want this page to rank for.')),
            Input::make()->name('seo_title')->label(__('SEO title'))->translatable()
                ->maxLength(70)->note(__('Leave empty to use the title template.')),
            Input::make()->name('seo_description')->label(__('Meta description'))->translatable()
                ->type('textarea')->rows(3)->maxLength(170),
        ];

        if ($advanced) {
            array_push(
                $fields,
                Input::make()->name('seo_canonical_url')->label(__('Canonical URL'))->translatable(),
                Checkbox::make()->name('seo_noindex')->label(__('No index')),
                Checkbox::make()->name('seo_nofollow')->label(__('No follow')),
                Checkbox::make()->name('seo_cornerstone')->label(__('Cornerstone content')),
            );
        }

        if ($social) {
            array_push(
                $fields,
                Input::make()->name('seo_og_title')->label(__('Social title'))->translatable(),
                Input::make()->name('seo_og_description')->label(__('Social description'))->translatable()->type('textarea'),
                Input::make()->name('seo_twitter_title')->label(__('Twitter title'))->translatable(),
                Input::make()->name('seo_twitter_description')->label(__('Twitter description'))->translatable()->type('textarea'),
                Medias::make()->name(self::OG_IMAGE_ROLE)->label(__('Share image'))->max(1),
            );
        }

        return Fieldset::make()->id('seo')->title(__('SEO'))->closed()->fields($fields);
    }
}

<?php

namespace TwillSeo\Services\Form;

use A17\Twill\Services\Forms\BladePartial;
use A17\Twill\Services\Forms\Fields\Checkbox;
use A17\Twill\Services\Forms\Fields\Input;
use A17\Twill\Services\Forms\Fields\Medias;
use A17\Twill\Services\Forms\Fieldset;
use A17\Twill\Services\Forms\Form;
use TwillSeo\Services\Settings\SeoSettings;

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
     * $analysis prepends the Vue editor panel (Task 6) as the fieldset's
     * first item, ahead of the plain seo_* inputs below it, whenever the
     * analysis feature is switched on globally; $social/$advanced let a host
     * trim the rest of the fieldset down to just the always-on keyphrase/
     * title/description trio.
     */
    public static function fieldset(bool $analysis = true, bool $social = true, bool $advanced = true, bool $open = false): Fieldset
    {
        $fields = [];

        // A static factory has no host model in scope to inject SeoSettings
        // through, so it is resolved via the container here — the same
        // pattern the analysis-panel partial itself already uses for
        // ModelRegistry. Reading through SeoSettings (DB row over config)
        // rather than a raw config() call is what lets the settings admin's
        // analysis toggle actually take effect without a deploy.
        if ($analysis && app(SeoSettings::class)->feature('analysis')) {
            $fields[] = self::analysisPanel();
        }

        array_push(
            $fields,
            // No ->note() on these: Twill renders notes beside the label,
            // and in the narrow side column they overlap it.
            Input::make()->name('seo_keyphrase')->label(__('Focus keyphrase'))->translatable(),
            Input::make()->name('seo_title')->label(__('SEO title'))->translatable()->maxLength(70),
            Input::make()->name('seo_description')->label(__('Meta description'))->translatable()
                ->type('textarea')->rows(3)->maxLength(170),
        );

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

        return Fieldset::make()->id('seo')->title(__('SEO'))->open($open)->fields($fields);
    }

    /**
     * The same fieldset packaged for a controller's getSideFieldsets(), so
     * the whole SEO section — panel, stoplights and inputs — sits in Twill's
     * right column under the publish widget instead of the main form:
     *
     *     public function getSideFieldsets(TwillModelContract $model): Form
     *     {
     *         return SeoFields::sideForm();
     *     }
     *
     * (Append to an existing side Form with ->add(SeoFields::fieldset())
     * when the module already has side fields of its own.)
     */
    public static function sideForm(bool $analysis = true, bool $social = true, bool $advanced = true): Form
    {
        // The fieldset must be routed through addFieldset(): Twill's
        // base_form renderer calls ->render() on every LOOSE Form item — a
        // method Fieldset does not have — and only the Form's dedicated
        // fieldsets collection reaches the fieldset-aware render path. A
        // Fieldset passed to Form::make([...]) therefore crashes the edit
        // page ("Call to undefined method Fieldset::render()").
        // Open in the sidebar: the stoplights ARE the point of putting the
        // section next to the publish widget — collapsed they say nothing.
        // (Main-form placements keep the collapsed default of fieldset().)
        $form = Form::make();
        $form->addFieldset(self::fieldset($analysis, $social, $advanced, open: true));

        return $form;
    }

    /**
     * The Task 6 Vue editor panel — a BladePartial rather than a typed field,
     * since only the surrounding form (via View::shared('form'), the exact
     * seam BladePartial::render() reads) knows which $item is being edited;
     * this factory itself never receives one. Deliberately no per-item logic
     * here: that lives entirely in resources/views/form/analysis-panel.blade.php,
     * which runs at render time rather than at fieldset-assembly time.
     */
    public static function analysisPanel(): BladePartial
    {
        return BladePartial::make()->view('twill-seo::form.analysis-panel');
    }

    /**
     * A compact, server-rendered (no JS) per-locale score summary for a
     * host's own sidebar or summary UI — not wired into fieldset() itself,
     * since where (or whether) a host wants this is its own call.
     */
    public static function sideChip(): BladePartial
    {
        return BladePartial::make()->view('twill-seo::form.score-chip');
    }
}

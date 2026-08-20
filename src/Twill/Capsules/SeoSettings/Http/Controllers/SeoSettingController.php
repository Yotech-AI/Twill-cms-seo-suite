<?php

namespace TwillSeo\Twill\Capsules\SeoSettings\Http\Controllers;

use A17\Twill\Http\Controllers\Admin\SingletonModuleController as BaseModuleController;
use A17\Twill\Models\Contracts\TwillModelContract;
use A17\Twill\Services\Forms\Fields\Checkbox;
use A17\Twill\Services\Forms\Fields\Files;
use A17\Twill\Services\Forms\Fields\Input;
use A17\Twill\Services\Forms\Fields\Medias;
use A17\Twill\Services\Forms\Fields\Select;
use A17\Twill\Services\Forms\Fieldset;
use A17\Twill\Services\Forms\Form;
use A17\Twill\Services\Forms\Options;
use TwillSeo\Services\ModelRegistry;
use TwillSeo\Twill\Capsules\SeoSettings\Models\SeoSetting;

/**
 * The SEO settings screen as a native Twill singleton: full content width,
 * Twill's own form fields, the file library for the logo (SVG-friendly) and
 * the media library for the default share image. Field names follow the
 * repository's mapping contract (general_* / feature_* / ct_{key}_* /
 * advanced_*).
 */
class SeoSettingController extends BaseModuleController
{
    /**
     * The schema.org types the per-content-type dropdown offers. A curated
     * list rather than a free Input so a typo cannot silently produce
     * invalid structured data. Covers both sides of the one decision the
     * package derives from this value — PageSeo::isArticleType() (Article,
     * *Article, *Posting -> og:type article + an extra Article graph node;
     * everything else -> website) — plus the common WebPage subtypes Google
     * documents. A type configured in code (the registry) or already stored
     * that falls outside this list is merged into the options at render
     * time, so exotic hosts keep round-tripping (see contentTypeFieldset()).
     */
    private const SCHEMA_TYPES = [
        'WebPage',
        'AboutPage',
        'ContactPage',
        'CollectionPage',
        'FAQPage',
        'ProfilePage',
        'SearchResultsPage',
        'Article',
        'BlogPosting',
        'NewsArticle',
        'TechArticle',
    ];

    /**
     * Override the module name without a typed property to maintain
     * compatibility with the parent controller which defines this
     * property without a type declaration.
     */
    protected $moduleName = 'seoSettings';

    protected function setUpController(): void
    {
        $this->disablePermalink();
        $this->disableEditor();
    }

    public function getForm(TwillModelContract $model): Form
    {
        $form = parent::getForm($model);

        $form->addFieldset($this->identityFieldset());
        $form->addFieldset($this->featuresFieldset());

        foreach (app(ModelRegistry::class)->all() as $registryKey => $registryEntry) {
            $form->addFieldset($this->contentTypeFieldset($registryKey, $registryEntry, $model));
        }

        $form->addFieldset($this->advancedFieldset());

        return $form;
    }

    private function identityFieldset(): Fieldset
    {
        return Fieldset::make()->id('identity')->title(__('Site identity'))->open(true)->fields([
            Input::make()->name('general_site_name')->label(__('Site name')),
            Input::make()->name('general_tagline')->label(__('Tagline')),
            Input::make()->name('general_separator')->label(__('Title separator'))->maxLength(5),
            Select::make()->name('general_entity_type')->label(__('Publisher is a'))
                ->options(Options::fromArray([
                    'organization' => __('Organization'),
                    'person' => __('Person'),
                ]))
                ->default('organization'),
            Input::make()->name('general_entity_name')->label(__('Publisher name')),
            // File library, not media library: logos are typically SVGs,
            // which the image pipeline cannot serve as-is. Files serve the
            // uploaded original untouched.
            Files::make()->name(SeoSetting::LOGO_ROLE)->label(__('Logo'))
                ->note(__('Prefer SVG or PNG — served exactly as uploaded')),
            Medias::make()->name(SeoSetting::DEFAULT_SHARE_ROLE)->label(__('Default share image'))->max(1),
            Input::make()->name('general_social_profiles')->label(__('Social profiles'))
                ->type('textarea')->rows(4)
                ->note(__('One URL per line')),
        ]);
    }

    private function featuresFieldset(): Fieldset
    {
        $labels = [
            'analysis' => __('Content analysis'),
            'sitemap' => __('XML sitemap'),
            'schema' => __('Schema.org structured data'),
            'og' => __('Open Graph tags'),
            'twitter' => __('Twitter card tags'),
            'hreflang' => __('Hreflang alternates'),
        ];

        $fields = [];

        foreach ($labels as $key => $label) {
            $fields[] = Checkbox::make()->name("feature_{$key}")->label($label);
        }

        return Fieldset::make()->id('features')->title(__('Features'))->fields($fields);
    }

    /**
     * @param  array<string,mixed>  $registryEntry
     */
    private function contentTypeFieldset(string $registryKey, array $registryEntry, TwillModelContract $model): Fieldset
    {
        return Fieldset::make()
            ->id('ct-'.str_replace('_', '-', $registryKey))
            ->title(__('Content type').': '.$registryKey)
            ->fields([
                Input::make()->name("ct_{$registryKey}_title_template")->label(__('Title template'))
                    ->note(__('Empty uses the default template')),
                Input::make()->name("ct_{$registryKey}_description_template")->label(__('Description template')),
                Select::make()->name("ct_{$registryKey}_schema_type")->label(__('Schema.org type'))
                    ->options(Options::fromArray($this->schemaTypeOptions($registryKey, $registryEntry, $model))),
                Checkbox::make()->name("ct_{$registryKey}_sitemap")->label(__('Include in the sitemap')),
            ]);
    }

    /**
     * SCHEMA_TYPES plus, when they fall outside it, this type's code-level
     * registry default and its currently stored override — without those a
     * host that configured an exotic type (e.g. Recipe) would see an empty
     * dropdown and saving would silently replace the value.
     *
     * @param  array<string,mixed>  $registryEntry
     * @return array<string,string>
     */
    private function schemaTypeOptions(string $registryKey, array $registryEntry, TwillModelContract $model): array
    {
        $types = self::SCHEMA_TYPES;

        $stored = (array) (((array) ($model->content_types ?? []))[$registryKey] ?? []);

        foreach ([$registryEntry['schema_type'] ?? null, $stored['schema_type'] ?? null] as $extra) {
            if (is_string($extra) && $extra !== '' && ! in_array($extra, $types, true)) {
                $types[] = $extra;
            }
        }

        return array_combine($types, $types);
    }

    private function advancedFieldset(): Fieldset
    {
        return Fieldset::make()->id('advanced')->title(__('Advanced'))->fields([
            Input::make()->name('advanced_robots_default_directives')->label(__('Default robots directives'))
                ->note(__('Comma separated')),
            Checkbox::make()->name('advanced_search_action_enabled')->label(__('Schema.org SearchAction')),
            Input::make()->name('advanced_search_url_template')->label(__('Search URL template'))
                ->note(__('Use {search_term_string} as the placeholder')),
            Checkbox::make()->name('advanced_uninstall_remove_data')->label(__('Remove data on uninstall')),
        ]);
    }
}

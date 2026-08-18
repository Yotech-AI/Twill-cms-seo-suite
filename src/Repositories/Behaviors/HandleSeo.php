<?php

namespace TwillSeo\Repositories\Behaviors;

use TwillSeo\Models\SeoEntry;

/**
 * Wires the package's seo_* form fields into Twill's save pipeline via the
 * trait-hook convention ModuleRepository::traitsMethods() dispatches
 * automatically (A17\Twill\Repositories\ModuleRepository::traitsMethods()).
 *
 * The core trick: every seo_* key is stashed then stripped out of $fields
 * before it ever reaches $model->fill(). A host model can coincidentally
 * have a real column named e.g. seo_title (see the Page fixture); if our
 * locale-keyed array leaked through to fill(), it would either silently
 * corrupt that column or blow up the save. Stashing-then-unsetting keeps the
 * two storage layers (host columns vs twill_seo_entries) fully isolated.
 *
 * IMPORTANT for hosts composing this trait alongside HandleTranslations: this
 * trait must be `use`d AFTER HandleTranslations. getFormFieldsHandleTranslations
 * unsets and rebuilds $fields['translations'] from scratch, which would wipe
 * out anything HandleSeo injected there if it ran first (see ArticleRepository).
 */
trait HandleSeo
{
    /**
     * Present-key snapshot of this save's seo_* input, keyed by form field
     * name. Only fields the request actually posted are stashed (see
     * stashSeoFields()), which is what makes flows that never send seo_*
     * keys (publish toggles, updateBasic) leave stored SEO data untouched.
     */
    private array $stashedSeoFields = [];

    /** Translated (locale-keyed) form field => twill_seo_entry_translations column. */
    private const TRANSLATED_FIELDS = [
        'seo_title' => 'seo_title',
        'seo_description' => 'seo_description',
        'seo_keyphrase' => 'focus_keyphrase',
        'seo_canonical_url' => 'canonical_url',
        'seo_og_title' => 'og_title',
        'seo_og_description' => 'og_description',
        'seo_twitter_title' => 'twitter_title',
        'seo_twitter_description' => 'twitter_description',
    ];

    /** Flat boolean form field => twill_seo_entries column. */
    private const FLAT_FIELDS = [
        'seo_noindex' => 'robots_noindex',
        'seo_nofollow' => 'robots_nofollow',
        'seo_cornerstone' => 'cornerstone',
    ];

    public function prepareFieldsBeforeCreateHandleSeo(array $fields): array
    {
        return $this->stashSeoFields($fields);
    }

    public function prepareFieldsBeforeSaveHandleSeo(?object $object, array $fields): array
    {
        return $this->stashSeoFields($fields);
    }

    private function stashSeoFields(array $fields): array
    {
        foreach ([...self::TRANSLATED_FIELDS, ...self::FLAT_FIELDS] as $formField => $column) {
            if (array_key_exists($formField, $fields)) {
                $this->stashedSeoFields[$formField] = $fields[$formField];
                unset($fields[$formField]);
            }
        }

        return $fields;
    }

    public function afterSaveHandleSeo(object $object, array $fields): void
    {
        if ($this->stashedSeoFields === []) {
            return;
        }

        /** @var SeoEntry $entry */
        $entry = $object->seoEntry()->firstOrCreate();

        foreach (self::FLAT_FIELDS as $formField => $column) {
            if (array_key_exists($formField, $this->stashedSeoFields)) {
                $entry->{$column} = (bool) $this->stashedSeoFields[$formField];
            }
        }

        if ($entry->isDirty()) {
            $entry->save();
        }

        foreach (self::TRANSLATED_FIELDS as $formField => $column) {
            if (! array_key_exists($formField, $this->stashedSeoFields)) {
                continue;
            }

            $value = $this->stashedSeoFields[$formField];

            // Untranslated host contexts (e.g. the Page fixture) post a
            // plain string rather than a locale-keyed array.
            $perLocale = is_array($value) ? $value : [app()->getLocale() => $value];

            foreach ($perLocale as $locale => $localeValue) {
                $entry->translationOrNew($locale)->{$column} = $this->trimToNull($localeValue);
            }
        }

        foreach ($entry->translations as $translation) {
            if ($translation->isDirty()) {
                $translation->save();
            }
        }

        $this->stashedSeoFields = [];
    }

    public function getFormFieldsHandleSeo(object $object, array $fields): array
    {
        $entry = $object->seoEntry;

        if (! $entry) {
            return $fields;
        }

        foreach ($entry->translations as $translation) {
            foreach (self::TRANSLATED_FIELDS as $formField => $column) {
                $fields['translations'][$formField][$translation->locale] = $translation->{$column};
            }
        }

        foreach (self::FLAT_FIELDS as $formField => $column) {
            $fields[$formField] = (bool) $entry->{$column};
        }

        return $fields;
    }

    public function afterDuplicateHandleSeo(object $old, object $new): void
    {
        $entry = $old->seoEntry;

        if (! $entry) {
            return;
        }

        $newEntry = $entry->replicate();
        $newEntry->seoable_id = $new->getKey();
        $newEntry->save();

        foreach ($entry->translations as $translation) {
            $newTranslation = $translation->replicate();
            $newTranslation->twill_seo_entry_id = $newEntry->id;

            // Analysis is per-copy, not inherited: the duplicate hasn't been
            // analyzed yet, even though its content starts out identical.
            $newTranslation->seo_score = null;
            $newTranslation->readability_score = null;
            $newTranslation->analysis_summary = null;
            $newTranslation->analyzed_at = null;

            $newTranslation->save();
        }
    }

    private function trimToNull(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}

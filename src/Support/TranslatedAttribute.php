<?php

namespace TwillSeo\Support;

/**
 * Reads one attribute from a Twill model at a specific locale, whether or not
 * the model is translatable.
 *
 * Duck-typed on `translate()` (the method Astrotomic's Translatable trait —
 * which HasTranslation composes — puts on the model) rather than an
 * instanceof check against HasTranslation: PaperFactory and
 * RenderedBlocksResolver both need this, and neither should have to know
 * which trait a host model happens to compose to get translation support.
 */
final class TranslatedAttribute
{
    public static function get(object $model, string $attribute, string $locale): ?string
    {
        if (method_exists($model, 'translate')) {
            /** @var object|null $translation */
            $translation = $model->translate($locale, false);

            return $translation?->{$attribute};
        }

        return $model->{$attribute} ?? null;
    }
}

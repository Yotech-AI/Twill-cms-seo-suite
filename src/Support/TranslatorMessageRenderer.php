<?php

namespace TwillSeo\Support;

use Illuminate\Contracts\Translation\Translator;
use TwillSeo\Analysis\Contracts\MessageRenderer;

/**
 * Renders assessment messages through Laravel's translator instead of the
 * engine's own file-based ArrayMessageRenderer.
 *
 * No translation needed between the two contracts: AnalysisContext::result()
 * already builds keys in Laravel's namespaced form
 * (twill-seo::analysis.<group>.<branch>), and the engine's :placeholder
 * syntax is the exact one Laravel's translator replaces. Deliberately NOT
 * locale-pinned to the paper being analyzed — these are feedback sentences
 * for the editor reading the panel, so they render in the admin's own
 * current locale (app()->getLocale()) the same way any other __() string in
 * the admin does, regardless of which locale the content itself is in.
 */
final class TranslatorMessageRenderer implements MessageRenderer
{
    public function __construct(private readonly Translator $translator) {}

    /**
     * @param  array<string,mixed>  $params
     */
    public function render(string $key, array $params): string
    {
        return $this->translator->get($key, $params);
    }
}

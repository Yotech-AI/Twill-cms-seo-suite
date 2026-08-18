<?php

namespace TwillSeo\Analysis\Research;

use TwillSeo\Analysis\Context\AnalysisContext;

/**
 * The reading ease score, or null when it would be meaningless.
 *
 * Null in three cases, all of them honest: the language cannot count syllables,
 * the language has no formula, or the text is too short for an average over its
 * sentences to mean anything. A made-up number would be worse than no number,
 * since the panel shows it as a fact rather than as a judgement.
 *
 * @implements Research<float|null>
 */
final class FleschReadingEase implements Research
{
    /** Below this the score swings wildly on one long word. */
    private const MINIMUM_WORDS = 10;

    private const SCALE_MINIMUM = 0.0;

    private const SCALE_MAXIMUM = 100.0;

    public function run(AnalysisContext $context): ?float
    {
        $counter = $context->language->syllableCounter();
        $formula = $context->language->fleschFormula();
        $words = $context->research(WordCount::class);

        if ($counter === null || $formula === null || $words <= self::MINIMUM_WORDS) {
            return null;
        }

        $sentences = max(1, count($context->research(Sentences::class)));

        $syllables = 0;

        foreach ($context->language->wordTokenizer()->tokenize($context->content->plainText) as $word) {
            $syllables += $counter->count($word);
        }

        $syllablesPerWord = $syllables / $words;

        $score = $formula->compute(
            $words / $sentences,
            $syllablesPerWord,
            $syllablesPerWord * 100,
        );

        // Clamped, because the formula is a line and has no opinion about
        // running off either end of its own scale.
        return round(max(self::SCALE_MINIMUM, min(self::SCALE_MAXIMUM, $score)), 1);
    }
}

<?php

namespace TwillSeo\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Research\KeyphraseContentWords;

/**
 * Whether the URL says what the page is about.
 *
 * The bar depends on the length of the keyphrase. A short phrase should be in
 * the slug whole; a long one should not be, because a slug that spells out five
 * words is unreadable and gets truncated everywhere it is shown. More than half
 * is the compromise.
 *
 * Nothing here scores worse than 6: a slug is often fixed after publication and
 * changing it costs the page its links, so this is advice rather than a fault.
 */
final class SlugKeywordAssessment implements Assessment
{
    /** From this many words on, most of the phrase is enough. */
    private const LONG_KEYPHRASE_FROM = 3;

    public function identifier(): string
    {
        return 'slugKeyword';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return true;
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        if (! $context->paper->hasKeyword() || ! $context->paper->hasSlug()) {
            return $context->result($this, 3, 'missing_input');
        }

        $contentWords = $context->research(KeyphraseContentWords::class);
        $matcher = $context->keyphraseMatcher();

        // The separators become spaces so the matcher sees the slug as words,
        // which is also what stops "tealeaf" from containing "tea".
        $slugWords = str_replace(['-', '_'], ' ', $context->paper->slug);

        $found = count(array_filter(
            $contentWords,
            fn (string $word) => $matcher->allWordsInText([$word], $slugWords),
        ));

        $total = count($contentWords);
        $params = ['count' => $found, 'total' => $total];

        $good = $total < self::LONG_KEYPHRASE_FROM
            ? $found === $total
            : $found > $total / 2;

        return $good
            ? $context->result($this, 9, 'good', $params)
            : $context->result($this, 6, 'some', $params);
    }
}

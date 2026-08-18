<?php

namespace TwillSeo\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Html\Heading;
use TwillSeo\Analysis\Research\KeyphraseContentWords;
use TwillSeo\Analysis\Research\WordCount;

/**
 * Whether the subheadings say what the page is about.
 *
 * Some of them should carry the keyphrase, not all of them: a page whose every
 * heading repeats the same phrase reads as written for a crawler, which is why
 * both too few and too many score the same fail.
 *
 * Only H2 and H3 count. An H1 is the page title rather than a section of it,
 * and H4 and below are usually inside a section rather than starting one.
 */
final class SubheadingsKeywordAssessment implements Assessment
{
    private const SUBHEADING_LEVELS = [2, 3];

    /** Under this many words a text does not need subheadings at all. */
    private const SUBHEADINGS_EXPECTED_FROM = 300;

    private const MINIMUM_RATIO = 0.30;

    private const MAXIMUM_RATIO = 0.75;

    public function identifier(): string
    {
        return 'subheadingsKeyword';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return true;
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        if (! $context->paper->hasKeyword() || ! $context->paper->hasText()) {
            return $context->result($this, 1, 'missing_input');
        }

        $subheadings = array_values(array_filter(
            $context->content->headings,
            fn (Heading $heading) => in_array($heading->level, self::SUBHEADING_LEVELS, true),
        ));

        $total = count($subheadings);

        if ($total === 0) {
            return $context->research(WordCount::class) >= self::SUBHEADINGS_EXPECTED_FROM
                ? $context->result($this, 2, 'none_long_text', ['count' => 0, 'total' => 0])
                : $context->result($this, 9, 'none_short_text', ['count' => 0, 'total' => 0]);
        }

        $contentWords = $context->research(KeyphraseContentWords::class);
        $matcher = $context->keyphraseMatcher();

        $matching = count(array_filter(
            $subheadings,
            fn (Heading $heading) => $matcher->allWordsInText($contentWords, $heading->text),
        ));

        $params = ['count' => $matching, 'total' => $total];
        $ratio = $matching / $total;

        return match (true) {
            $matching === 0 => $context->result($this, 3, 'none', $params),
            // A text with one subheading has no ratio to speak of: either that
            // heading carries the keyphrase or the branch above already fired.
            $total === 1 => $context->result($this, 9, 'good', $params),
            $ratio < self::MINIMUM_RATIO => $context->result($this, 3, 'too_few', $params),
            $ratio <= self::MAXIMUM_RATIO => $context->result($this, 9, 'good', $params),
            default => $context->result($this, 3, 'too_many', $params),
        };
    }
}

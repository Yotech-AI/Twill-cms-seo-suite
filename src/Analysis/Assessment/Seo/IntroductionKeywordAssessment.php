<?php

namespace TwillSeo\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Research\FirstParagraph;
use TwillSeo\Analysis\Research\KeyphraseContentWords;

/**
 * Whether the opening paragraph says what the page is about.
 *
 * Readers and search engines both decide from the first few lines whether the
 * page answers their question, so the keyphrase belongs there. Held together in
 * one sentence scores full marks; merely present somewhere in the paragraph is
 * a partial pass, because the phrase is there but the claim is not.
 */
final class IntroductionKeywordAssessment implements Assessment
{
    public function identifier(): string
    {
        return 'introductionKeyword';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return $context->paper->hasText() && $context->paper->hasKeyword();
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        $sentences = $context->research(FirstParagraph::class);
        $contentWords = $context->research(KeyphraseContentWords::class);
        $matcher = $context->keyphraseMatcher();

        if ($sentences === []) {
            return $context->result($this, 3, 'none');
        }

        return match (true) {
            $matcher->allWordsInOneSentence($contentWords, $sentences) => $context->result($this, 9, 'good'),
            $matcher->allWordsInText($contentWords, implode(' ', $sentences)) => $context->result($this, 6, 'spread'),
            default => $context->result($this, 3, 'none'),
        };
    }
}

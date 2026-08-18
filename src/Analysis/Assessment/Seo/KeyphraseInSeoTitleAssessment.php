<?php

namespace TwillSeo\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Research\KeyphraseContentWords;

/**
 * Whether the SEO title contains the keyphrase, and how early.
 *
 * The title is the strongest signal a page has, and a searcher scanning results
 * reads its first words. Having the phrase at the front is therefore worth more
 * than having it at the end, and having the words scattered through the title
 * is worth less than having the phrase itself.
 */
final class KeyphraseInSeoTitleAssessment implements Assessment
{
    public function identifier(): string
    {
        // The acronym stays capitalised: this identifier is part of the report
        // contract the editor panel reads.
        return 'keyphraseInSEOTitle';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return true;
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        if (! $context->paper->hasKeyword() || ! $context->paper->hasTitle()) {
            return $context->result($this, 2, 'missing_input');
        }

        $matcher = $context->keyphraseMatcher();
        $title = $context->paper->title;
        $position = $matcher->exactPhrasePosition($title, $context->paper->keyword);

        return match (true) {
            $position === 0 => $context->result($this, 9, 'good_start'),
            $position !== null => $context->result($this, 6, 'good_not_start'),
            $matcher->allWordsInText($context->research(KeyphraseContentWords::class), $title) => $context->result($this, 6, 'all_words'),
            default => $context->result($this, 2, 'not_found'),
        };
    }
}

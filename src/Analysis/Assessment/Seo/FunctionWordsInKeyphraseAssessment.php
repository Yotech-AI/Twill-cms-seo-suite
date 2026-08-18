<?php

namespace TwillSeo\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;

/**
 * Warns about a keyphrase with no content in it — "about us", "how to do it".
 *
 * The only assessment that says nothing at all when the news is good: there is
 * no such thing as a keyphrase that passes this check, only ones it has no
 * complaint about. It scores 0, the feedback sentinel, so a warning about the
 * keyphrase itself never counts as a failed check about the page.
 */
final class FunctionWordsInKeyphraseAssessment implements Assessment
{
    public function identifier(): string
    {
        return 'functionWordsInKeyphrase';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return $context->paper->hasKeyword()
            && $context->keyphraseMatcher()->isOnlyFunctionWords($context->paper->keyword, $context->language);
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        return $context->result($this, 0, 'only_function_words');
    }
}

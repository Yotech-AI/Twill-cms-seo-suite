<?php

namespace TwillSeo\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;

/**
 * Whether the meta description contains the keyphrase.
 *
 * Search engines bold the searched words in the snippet, so having it there
 * once earns the click. Having it three times spends the only two lines the
 * page gets in the results on repetition.
 */
final class MetaDescriptionKeywordAssessment implements Assessment
{
    private const MAXIMUM_OCCURRENCES = 2;

    public function identifier(): string
    {
        return 'metaDescriptionKeyword';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return $context->paper->hasKeyword();
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        if (! $context->paper->hasDescription()) {
            // The length assessment is the one that asks for a description at
            // all; this one only says why it matters here.
            return $context->result($this, 3, 'missing_description', ['count' => 0]);
        }

        $count = $context->keyphraseMatcher()->countOccurrences(
            $context->paper->keyword,
            $context->language->sentenceTokenizer()->tokenize($context->paper->description),
            $context->language,
        );

        $params = ['count' => $count];

        return match (true) {
            $count === 0 => $context->result($this, 3, 'none', $params),
            $count <= self::MAXIMUM_OCCURRENCES => $context->result($this, 9, 'good', $params),
            default => $context->result($this, 3, 'too_many', $params),
        };
    }
}

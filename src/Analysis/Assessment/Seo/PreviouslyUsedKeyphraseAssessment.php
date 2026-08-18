<?php

namespace TwillSeo\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Research\KeyphraseUsageCount;

/**
 * Whether the site is already targeting this keyphrase somewhere else.
 *
 * Two pages after the same search compete with each other, and the search
 * engine picks one of them — usually not the one the author would have chosen.
 *
 * The whole assessment disappears when the host cannot answer, rather than
 * reporting a reassuring zero it has no grounds for.
 */
final class PreviouslyUsedKeyphraseAssessment implements Assessment
{
    public function identifier(): string
    {
        return 'previouslyUsedKeyphrase';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return $context->research(KeyphraseUsageCount::class) !== null;
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        if (! $context->paper->hasKeyword()) {
            return $context->result($this, 1, 'missing_keyphrase');
        }

        $count = (int) $context->research(KeyphraseUsageCount::class);
        $params = ['count' => $count];

        return match (true) {
            $count === 0 => $context->result($this, 9, 'unique', $params),
            $count === 1 => $context->result($this, 6, 'used_once', $params),
            default => $context->result($this, 1, 'used_multiple', $params),
        };
    }
}

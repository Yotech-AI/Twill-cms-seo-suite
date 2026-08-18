<?php

namespace TwillSeo\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;

/**
 * A page says what it is about once. Several H1s in the body leave no single
 * answer to that.
 *
 * Zero H1s in the body is fine here: the page template usually supplies the
 * one H1 and the editor field only holds the body.
 */
final class SingleH1Assessment implements Assessment
{
    public function identifier(): string
    {
        return 'singleH1';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return $context->paper->hasText();
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        $count = $context->content->countHeadingsOfLevel(1);

        return $count > 1
            ? $context->result($this, 1, 'multiple', ['count' => $count])
            : $context->result($this, 8, 'good', ['count' => $count]);
    }
}

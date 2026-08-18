<?php

namespace TwillSeo\Analysis\Assessment;

use TwillSeo\Analysis\Context\AnalysisContext;

interface Assessment
{
    /** Stable camelCase id; also the key the panel and the message file use. */
    public function identifier(): string;

    /**
     * Whether this assessment can say anything about this paper. An
     * inapplicable assessment is left out entirely rather than scored, so it
     * neither helps nor hurts the total.
     */
    public function isApplicable(AnalysisContext $context): bool;

    public function assess(AnalysisContext $context): AssessmentResult;
}

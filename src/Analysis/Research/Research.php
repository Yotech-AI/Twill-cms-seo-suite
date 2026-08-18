<?php

namespace TwillSeo\Analysis\Research;

use TwillSeo\Analysis\Context\AnalysisContext;

/**
 * A single derived fact about a paper — word count, sentences, link
 * statistics. Split out from the assessments because several assessments ask
 * the same question and the answer is expensive enough to want computed once
 * per run.
 *
 * Implementations must be constructible with no arguments: the context
 * instantiates them by class name.
 *
 * @template TResult
 */
interface Research
{
    /**
     * @return TResult
     */
    public function run(AnalysisContext $context): mixed;
}

<?php

namespace TwillSeo\Analysis\Research;

use TwillSeo\Analysis\Context\AnalysisContext;

/**
 * How often the keyphrase occurs in the body text, counted per sentence so a
 * phrase is never counted across a full stop.
 *
 * @implements Research<int>
 */
final class KeywordCount implements Research
{
    public function run(AnalysisContext $context): int
    {
        return $context->keyphraseMatcher()->countOccurrences(
            $context->paper->keyword,
            $context->research(Sentences::class),
            $context->language,
        );
    }
}

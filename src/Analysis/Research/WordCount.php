<?php

namespace TwillSeo\Analysis\Research;

use TwillSeo\Analysis\Context\AnalysisContext;

/**
 * @implements Research<int>
 */
final class WordCount implements Research
{
    public function run(AnalysisContext $context): int
    {
        return $context->language->wordTokenizer()->count($context->content->plainText);
    }
}

<?php

namespace TwillSeo\Analysis\Research;

use TwillSeo\Analysis\Context\AnalysisContext;

/**
 * @implements Research<list<string>>
 */
final class Sentences implements Research
{
    /**
     * @return list<string>
     */
    public function run(AnalysisContext $context): array
    {
        return $context->language->sentenceTokenizer()->tokenize($context->content->plainText);
    }
}

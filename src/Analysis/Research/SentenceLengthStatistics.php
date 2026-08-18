<?php

namespace TwillSeo\Analysis\Research;

use TwillSeo\Analysis\Context\AnalysisContext;

/**
 * How many words each sentence of the text runs to, in document order.
 *
 * @implements Research<list<int>>
 */
final class SentenceLengthStatistics implements Research
{
    /**
     * @return list<int>
     */
    public function run(AnalysisContext $context): array
    {
        $tokenizer = $context->language->wordTokenizer();

        return array_map(
            fn (string $sentence) => $tokenizer->count($sentence),
            $context->research(Sentences::class),
        );
    }
}

<?php

namespace TwillSeo\Analysis\Research;

use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Html\Paragraph;

/**
 * How many words each paragraph runs to, in document order.
 *
 * @implements Research<list<int>>
 */
final class ParagraphLengths implements Research
{
    /**
     * @return list<int>
     */
    public function run(AnalysisContext $context): array
    {
        $tokenizer = $context->language->wordTokenizer();

        return array_map(
            fn (Paragraph $paragraph) => $tokenizer->count($paragraph->text),
            $context->content->paragraphs,
        );
    }
}

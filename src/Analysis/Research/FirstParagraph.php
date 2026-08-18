<?php

namespace TwillSeo\Analysis\Research;

use TwillSeo\Analysis\Context\AnalysisContext;

/**
 * The sentences of the text's opening paragraph.
 *
 * Sentences rather than one string, because the introduction assessment cares
 * whether the keyphrase lands inside a single sentence or is merely scattered
 * across the paragraph.
 *
 * @implements Research<list<string>>
 */
final class FirstParagraph implements Research
{
    /**
     * @return list<string>
     */
    public function run(AnalysisContext $context): array
    {
        $first = $context->content->paragraphs[0] ?? null;

        return $first === null ? [] : $context->language->sentenceTokenizer()->tokenize($first->text);
    }
}

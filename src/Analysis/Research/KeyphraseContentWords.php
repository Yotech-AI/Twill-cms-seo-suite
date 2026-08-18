<?php

namespace TwillSeo\Analysis\Research;

use TwillSeo\Analysis\Context\AnalysisContext;

/**
 * The words of the keyphrase that a text actually has to contain.
 *
 * Seven assessments ask this same question, and the answer costs a tokenize
 * plus a pass over the function word list every time, so it is computed once.
 *
 * @implements Research<list<string>>
 */
final class KeyphraseContentWords implements Research
{
    /**
     * @return list<string>
     */
    public function run(AnalysisContext $context): array
    {
        return $context->keyphraseMatcher()->contentWords($context->paper->keyword, $context->language);
    }
}

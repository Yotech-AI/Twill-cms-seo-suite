<?php

namespace TwillSeo\Analysis\Research;

use TwillSeo\Analysis\Context\AnalysisContext;

/**
 * How many other pages already target this keyphrase, according to the host.
 *
 * The only research that leaves the engine: it asks the host's provider, which
 * in a real installation is a database query. The memo is what keeps that to
 * one query per analysis however many assessments ask.
 *
 * Null means the host cannot answer, which is not zero — the assessment that
 * asks has to treat the two differently.
 *
 * @implements Research<int|null>
 */
final class KeyphraseUsageCount implements Research
{
    public function run(AnalysisContext $context): ?int
    {
        return $context->keyphraseUsage->countOtherUsages($context->paper->keyword, $context->paper);
    }
}

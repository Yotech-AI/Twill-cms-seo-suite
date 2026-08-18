<?php

namespace TwillSeo\Analysis\Research;

use TwillSeo\Analysis\Context\AnalysisContext;

/**
 * How many sentences are written in the passive voice.
 *
 * @implements Research<int>
 */
final class PassiveVoiceStatistics implements Research
{
    public function run(AnalysisContext $context): int
    {
        $detector = $context->language->passiveVoice();

        if ($detector === null) {
            return 0;
        }

        return count(array_filter(
            $context->research(Sentences::class),
            fn (string $sentence) => $detector->isPassive($sentence),
        ));
    }
}

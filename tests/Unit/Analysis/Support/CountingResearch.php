<?php

namespace TwillSeo\Tests\Unit\Analysis\Support;

use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Research\Research;

/**
 * Counts how often it actually ran, so the context's memo can be observed
 * rather than assumed.
 *
 * @implements Research<int>
 */
final class CountingResearch implements Research
{
    public static int $runs = 0;

    public function run(AnalysisContext $context): int
    {
        self::$runs++;

        return self::$runs;
    }
}

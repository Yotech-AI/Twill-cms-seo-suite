<?php

namespace TwillSeo\Tests\Unit\Analysis\Support;

use TwillSeo\Analysis\Contracts\KeyphraseUsageProvider;
use TwillSeo\Analysis\Paper\Paper;

/**
 * A host that knows how often a keyphrase is used, and counts how often it was
 * asked — the query is a database round trip in a real host, so the engine
 * making it twice would be a defect.
 *
 * Not a test file — PHPUnit only collects *Test.php.
 */
final class CountingUsageProvider implements KeyphraseUsageProvider
{
    public int $calls = 0;

    /**
     * @param  int|null  $usages  null is the host answering "I cannot tell"
     */
    public function __construct(private readonly ?int $usages) {}

    public function countOtherUsages(string $keyphrase, Paper $paper): ?int
    {
        $this->calls++;

        return $this->usages;
    }
}

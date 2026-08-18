<?php

namespace TwillSeo\Analysis\Support;

use TwillSeo\Analysis\Contracts\KeyphraseUsageProvider;
use TwillSeo\Analysis\Paper\Paper;

/**
 * The provider used when no host supplied one: it answers "I don't know"
 * rather than "zero", so a keyphrase never reads as unique just because
 * nothing was wired up.
 */
final class NullKeyphraseUsageProvider implements KeyphraseUsageProvider
{
    public function countOtherUsages(string $keyphrase, Paper $paper): ?int
    {
        return null;
    }
}

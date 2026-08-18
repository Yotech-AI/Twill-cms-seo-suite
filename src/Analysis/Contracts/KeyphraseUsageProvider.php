<?php

namespace TwillSeo\Analysis\Contracts;

use TwillSeo\Analysis\Paper\Paper;

/**
 * Answers "is this keyphrase already used elsewhere on the site". Only the
 * host knows, since it owns the content store.
 */
interface KeyphraseUsageProvider
{
    /**
     * @return int|null number of other pages using $keyphrase, or null when the host
     *                  cannot answer — which is not the same as zero, and the
     *                  assessment that asks must treat it differently
     */
    public function countOtherUsages(string $keyphrase, Paper $paper): ?int;
}

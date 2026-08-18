<?php

namespace TwillSeo\Analysis\Research;

use TwillSeo\Analysis\Context\AnalysisContext;

/**
 * The word each sentence really begins with, case folded.
 *
 * "Really" because a determiner is not what a sentence is about: "The cat sat.
 * The dog barked." opens twice with "the" and says two different things, so the
 * comparison steps over the words the language pack lists as exceptions.
 *
 * Sentences with no words in them are left out rather than recorded as an empty
 * beginning, which would otherwise break a run of repetitions in two.
 *
 * @implements Research<list<string>>
 */
final class SentenceBeginnings implements Research
{
    /**
     * @return list<string>
     */
    public function run(AnalysisContext $context): array
    {
        $exceptions = $context->language->firstWordExceptions();
        $tokenizer = $context->language->wordTokenizer();

        $beginnings = [];

        foreach ($context->research(Sentences::class) as $sentence) {
            $words = $tokenizer->tokenize($sentence);

            if ($words === []) {
                continue;
            }

            // Only one word is stepped over: "The the cat" is not a sentence
            // anyone writes, and skipping a run of them could walk past the
            // subject entirely.
            $first = $exceptions !== null && $exceptions->contains($words[0]) && isset($words[1])
                ? $words[1]
                : $words[0];

            $beginnings[] = mb_strtolower($first);
        }

        return $beginnings;
    }
}

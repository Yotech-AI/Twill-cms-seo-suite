<?php

namespace TwillSeo\Analysis\Research;

use TwillSeo\Analysis\Context\AnalysisContext;

/**
 * The sentences of the text's opening PROSE paragraph.
 *
 * Rendered templates put small UI fragments ahead of the real introduction —
 * an eyebrow label ("Diensten"), a CTA button ("Plan een call") — and each of
 * those parses as a paragraph of its own. An introduction is prose, so leading
 * fragments that are neither sentence-like (no terminator) nor substantial
 * (under the word floor) are skipped; the first paragraph that reads as prose
 * is the introduction. A text made up ONLY of fragments falls back to its
 * first paragraph, keeping the empty/none behavior identical to before.
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
     * Below this many words, a paragraph without sentence punctuation is a
     * label, not an introduction.
     */
    private const PROSE_WORD_FLOOR = 10;

    /**
     * @return list<string>
     */
    public function run(AnalysisContext $context): array
    {
        $paragraphs = $context->content->paragraphs;

        if ($paragraphs === []) {
            return [];
        }

        $first = null;

        foreach ($paragraphs as $paragraph) {
            if ($this->isProse($paragraph->text, $context)) {
                $first = $paragraph;
                break;
            }
        }

        $first ??= $paragraphs[0];

        return $context->language->sentenceTokenizer()->tokenize($first->text);
    }

    private function isProse(string $text, AnalysisContext $context): bool
    {
        if (preg_match('/[.!?…]/u', $text) === 1) {
            return true;
        }

        return count($context->language->wordTokenizer()->tokenize($text)) >= self::PROSE_WORD_FLOOR;
    }
}

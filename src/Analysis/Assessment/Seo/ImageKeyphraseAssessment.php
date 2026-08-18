<?php

namespace TwillSeo\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Html\Image;
use TwillSeo\Analysis\Research\KeyphraseContentWords;

/**
 * Whether the images are described in the words the page is about.
 *
 * Alt text is what a search engine and a screen reader have instead of the
 * picture, so it should say what the picture shows — in the page's own subject
 * matter where that is honest. Nothing here scores worse than 6: alt text that
 * describes the image accurately without using the keyphrase is good alt text,
 * and telling an author otherwise would be telling them to lie.
 */
final class ImageKeyphraseAssessment implements Assessment
{
    /** Below this a set of images is too small for a share to mean anything. */
    private const RATIO_APPLIES_FROM = 5;

    private const MINIMUM_RATIO = 0.30;

    private const MAXIMUM_RATIO = 0.75;

    public function identifier(): string
    {
        return 'imageKeyphrase';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return true;
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        $images = $context->content->images;
        $total = count($images);

        if ($total === 0 || ! $context->paper->hasKeyword()) {
            return $context->result($this, 3, 'missing_input', ['count' => 0, 'total' => $total]);
        }

        $described = array_values(array_filter($images, fn (Image $image) => trim((string) $image->alt) !== ''));

        if ($described === []) {
            return $context->result($this, 6, 'no_alts', ['count' => 0, 'total' => $total]);
        }

        $contentWords = $context->research(KeyphraseContentWords::class);
        $matcher = $context->keyphraseMatcher();

        $matching = count(array_filter(
            $described,
            fn (Image $image) => $matcher->allWordsInText($contentWords, (string) $image->alt),
        ));

        $params = ['count' => $matching, 'total' => $total];
        $ratio = $matching / $total;

        return match (true) {
            $matching === 0 => $context->result($this, 6, 'none_match', $params),
            // One good alt text is the whole of the advice for a page with a
            // picture or two; a share only makes sense for a gallery.
            $total < self::RATIO_APPLIES_FROM => $context->result($this, 9, 'good', $params),
            $ratio < self::MINIMUM_RATIO => $context->result($this, 6, 'too_few', $params),
            $ratio <= self::MAXIMUM_RATIO => $context->result($this, 9, 'good', $params),
            default => $context->result($this, 6, 'too_many', $params),
        };
    }
}

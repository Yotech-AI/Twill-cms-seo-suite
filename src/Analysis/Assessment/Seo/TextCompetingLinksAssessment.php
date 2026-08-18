<?php

namespace TwillSeo\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Html\Link;

/**
 * Links whose anchor text is the keyphrase this page is trying to rank for.
 *
 * Anchor text tells a search engine what the page at the other end is about,
 * so a link labelled with this page's own keyphrase is an argument that some
 * other page deserves the ranking. The exact phrase has to be there: the words
 * merely occurring in a longer sentence of anchor text is not a competing
 * claim.
 */
final class TextCompetingLinksAssessment implements Assessment
{
    public function identifier(): string
    {
        return 'textCompetingLinks';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return $context->paper->hasText() && $context->paper->hasKeyword();
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        $matcher = $context->keyphraseMatcher();
        $keyphrase = $context->paper->keyword;

        $competing = count(array_filter(
            $context->content->links,
            fn (Link $link) => $matcher->containsExactPhrase($link->anchorText, $keyphrase),
        ));

        $params = ['count' => $competing];

        return $competing === 0
            ? $context->result($this, 8, 'good', $params)
            : $context->result($this, 2, 'competing', $params);
    }
}

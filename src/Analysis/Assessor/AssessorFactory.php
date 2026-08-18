<?php

namespace TwillSeo\Analysis\Assessor;

use TwillSeo\Analysis\Assessment\Config\TextLengthThresholds;
use TwillSeo\Analysis\Assessment\Seo\ExternalLinksAssessment;
use TwillSeo\Analysis\Assessment\Seo\FunctionWordsInKeyphraseAssessment;
use TwillSeo\Analysis\Assessment\Seo\ImageKeyphraseAssessment;
use TwillSeo\Analysis\Assessment\Seo\ImagesAssessment;
use TwillSeo\Analysis\Assessment\Seo\InternalLinksAssessment;
use TwillSeo\Analysis\Assessment\Seo\IntroductionKeywordAssessment;
use TwillSeo\Analysis\Assessment\Seo\KeyphraseInSeoTitleAssessment;
use TwillSeo\Analysis\Assessment\Seo\KeyphraseLengthAssessment;
use TwillSeo\Analysis\Assessment\Seo\KeywordDensityAssessment;
use TwillSeo\Analysis\Assessment\Seo\MetaDescriptionKeywordAssessment;
use TwillSeo\Analysis\Assessment\Seo\MetaDescriptionLengthAssessment;
use TwillSeo\Analysis\Assessment\Seo\PreviouslyUsedKeyphraseAssessment;
use TwillSeo\Analysis\Assessment\Seo\SingleH1Assessment;
use TwillSeo\Analysis\Assessment\Seo\SlugKeywordAssessment;
use TwillSeo\Analysis\Assessment\Seo\SubheadingsKeywordAssessment;
use TwillSeo\Analysis\Assessment\Seo\TextCompetingLinksAssessment;
use TwillSeo\Analysis\Assessment\Seo\TextLengthAssessment;
use TwillSeo\Analysis\Assessment\Seo\TitleWidthAssessment;
use TwillSeo\Analysis\Score\ReadabilityPenaltyAggregator;
use TwillSeo\Analysis\Score\SeoScoreAggregator;

/**
 * The registry of what gets assessed. The array order below is the order the
 * editor panel lists results in.
 */
final class AssessorFactory
{
    public function seo(bool $cornerstone = false): Assessor
    {
        return new Assessor([
            new IntroductionKeywordAssessment,
            new KeyphraseLengthAssessment,
            new KeywordDensityAssessment,
            new MetaDescriptionKeywordAssessment,
            new MetaDescriptionLengthAssessment,
            new SubheadingsKeywordAssessment,
            new TextCompetingLinksAssessment,
            new ImageKeyphraseAssessment,
            new ImagesAssessment,
            new TextLengthAssessment($cornerstone ? TextLengthThresholds::cornerstone() : TextLengthThresholds::default()),
            new ExternalLinksAssessment,
            new KeyphraseInSeoTitleAssessment,
            new InternalLinksAssessment,
            new TitleWidthAssessment,
            new SlugKeywordAssessment,
            new FunctionWordsInKeyphraseAssessment,
            new SingleH1Assessment,
            new PreviouslyUsedKeyphraseAssessment,
        ], new SeoScoreAggregator);
    }

    /**
     * @param  bool  $cornerstone  unused while the list is empty; kept because the
     *                             readability assessments landing here have cornerstone
     *                             variants of their own
     */
    public function readability(bool $cornerstone = false): Assessor
    {
        // Empty on purpose: the readability assessments need the language
        // packs, which do not exist yet. The aggregator turns an empty list
        // into "not available", which is the honest answer meanwhile.
        return new Assessor([], new ReadabilityPenaltyAggregator);
    }
}

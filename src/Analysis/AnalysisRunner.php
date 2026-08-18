<?php

namespace TwillSeo\Analysis;

use TwillSeo\Analysis\Assessor\AssessorFactory;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Contracts\KeyphraseUsageProvider;
use TwillSeo\Analysis\Contracts\MessageRenderer;
use TwillSeo\Analysis\Html\HtmlParser;
use TwillSeo\Analysis\Language\LanguagePackRegistry;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Analysis\Report\AnalysisReport;
use TwillSeo\Analysis\Report\Insights;
use TwillSeo\Analysis\Report\ScoreSection;
use TwillSeo\Analysis\Research\WordCount;
use TwillSeo\Analysis\Support\NullKeyphraseUsageProvider;

/**
 * The engine's front door: paper in, report out.
 *
 * Everything happens once here — the HTML is parsed once, the language pack
 * resolved once, and one context carries the memo for both assessors, so the
 * SEO and readability passes share every derived fact.
 */
final class AnalysisRunner
{
    private readonly KeyphraseUsageProvider $keyphraseUsage;

    public function __construct(
        private readonly HtmlParser $parser,
        private readonly LanguagePackRegistry $languages,
        private readonly AssessorFactory $assessors,
        private readonly MessageRenderer $messages,
        ?KeyphraseUsageProvider $keyphraseUsage = null,
    ) {
        $this->keyphraseUsage = $keyphraseUsage ?? new NullKeyphraseUsageProvider;
    }

    public function analyze(Paper $paper, ?AnalysisOptions $options = null): AnalysisReport
    {
        $options ??= new AnalysisOptions;
        $language = $this->languages->forLocale($paper->locale);

        $context = new AnalysisContext(
            $paper,
            // The parser never throws: bad markup analyses as empty content,
            // so a broken paragraph cannot take the whole report down.
            $this->parser->parse($paper->text, $paper->permalink),
            $language,
            $this->messages,
            $this->keyphraseUsage,
        );

        return new AnalysisReport(
            $paper->languageCode(),
            $language->supportsFullReadability(),
            $options->seo
                ? ScoreSection::fromAssessorResult($this->assessors->seo($options->cornerstone)->run($context))
                : ScoreSection::empty(),
            $options->readability
                ? ScoreSection::fromAssessorResult($this->assessors->readability($options->cornerstone)->run($context))
                : ScoreSection::empty(),
            // Reuses the word count the text length assessment already asked
            // for, through the context memo.
            $options->insights ? Insights::forWordCount($context->research(WordCount::class)) : null,
        );
    }
}

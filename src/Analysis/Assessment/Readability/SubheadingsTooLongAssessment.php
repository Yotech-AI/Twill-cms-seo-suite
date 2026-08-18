<?php

namespace TwillSeo\Analysis\Assessment\Readability;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Html\Heading;
use TwillSeo\Analysis\Research\SubheadingSectionLengths;
use TwillSeo\Analysis\Research\WordCount;

/**
 * How far a reader gets between one subheading and the next.
 *
 * Subheadings are what let someone scan a page and find the part they came
 * for, so a long text without them is a problem in itself — and a long text
 * with them can still hide a section nobody will read to the end of.
 */
final class SubheadingsTooLongAssessment implements Assessment
{
    private const SUBHEADING_LEVELS = [2, 3];

    /**
     * Up to and including this many words, a text is short enough to read
     * straight through. Note that the keyphrase-in-subheadings assessment
     * expects subheadings from exactly 300 words while this one does not: the
     * two published thresholds disagree by one word, and both are implemented
     * as published rather than quietly reconciled.
     */
    private const SHORT_TEXT_UP_TO = 300;

    private const MAXIMUM_SECTION_WORDS = 300;

    private const LONG_SECTION_WORDS = 350;

    public function identifier(): string
    {
        return 'subheadingsTooLong';
    }

    public function isApplicable(AnalysisContext $context): bool
    {
        return $context->paper->hasText();
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        $words = $context->research(WordCount::class);

        if ($words <= self::SHORT_TEXT_UP_TO) {
            return $context->result($this, 9, 'short_text', ['words' => $words, 'max' => self::MAXIMUM_SECTION_WORDS]);
        }

        $subheadings = array_filter(
            $context->content->headings,
            fn (Heading $heading) => in_array($heading->level, self::SUBHEADING_LEVELS, true),
        );

        if ($subheadings === []) {
            return $context->result($this, 2, 'none', ['words' => $words, 'max' => self::MAXIMUM_SECTION_WORDS]);
        }

        $sections = $context->research(SubheadingSectionLengths::class);
        $longest = $sections === [] ? 0 : max($sections);
        $params = ['words' => $longest, 'max' => self::MAXIMUM_SECTION_WORDS];

        return match (true) {
            $longest <= self::MAXIMUM_SECTION_WORDS => $context->result($this, 9, 'good', $params),
            $longest <= self::LONG_SECTION_WORDS => $context->result($this, 6, 'long_section', $params),
            default => $context->result($this, 3, 'too_long_section', $params),
        };
    }
}

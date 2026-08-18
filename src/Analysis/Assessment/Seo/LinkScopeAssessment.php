<?php

namespace TwillSeo\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Research\LinkStatistics;
use TwillSeo\Analysis\Research\LinkStatisticsResult;

/**
 * Internal and external links are judged on the same scale — are there any,
 * and do they pass value — so the scale lives here once. The subclasses differ
 * only in which pair of numbers they read and, through their identifier, which
 * messages they show.
 */
abstract class LinkScopeAssessment implements Assessment
{
    public function isApplicable(AnalysisContext $context): bool
    {
        return $context->paper->hasText();
    }

    public function assess(AnalysisContext $context): AssessmentResult
    {
        $statistics = $context->research(LinkStatistics::class);

        $total = $this->totalLinks($statistics);
        $nofollow = $this->nofollowLinks($statistics);
        $params = ['total' => $total, 'nofollow' => $nofollow];

        return match (true) {
            $total === 0 => $context->result($this, 3, 'none', $params),
            $nofollow === $total => $context->result($this, 7, 'all_nofollow', $params),
            $nofollow > 0 => $context->result($this, 8, 'some_nofollow', $params),
            default => $context->result($this, 9, 'good', $params),
        };
    }

    abstract protected function totalLinks(LinkStatisticsResult $statistics): int;

    abstract protected function nofollowLinks(LinkStatisticsResult $statistics): int;
}

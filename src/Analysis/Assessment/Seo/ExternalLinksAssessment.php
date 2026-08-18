<?php

namespace TwillSeo\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Research\LinkStatisticsResult;

/**
 * Links to other sites. Citing sources is what a page about a subject
 * generally does, and its absence is a signal in itself.
 */
final class ExternalLinksAssessment extends LinkScopeAssessment
{
    public function identifier(): string
    {
        return 'externalLinks';
    }

    protected function totalLinks(LinkStatisticsResult $statistics): int
    {
        return $statistics->externalTotal;
    }

    protected function nofollowLinks(LinkStatisticsResult $statistics): int
    {
        return $statistics->externalNofollow;
    }
}

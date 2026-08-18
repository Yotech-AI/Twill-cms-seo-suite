<?php

namespace TwillSeo\Analysis\Assessment\Seo;

use TwillSeo\Analysis\Research\LinkStatisticsResult;

/**
 * Links to the site's own pages. Without them a page is a dead end for both
 * readers and crawlers.
 */
final class InternalLinksAssessment extends LinkScopeAssessment
{
    public function identifier(): string
    {
        return 'internalLinks';
    }

    protected function totalLinks(LinkStatisticsResult $statistics): int
    {
        return $statistics->internalTotal;
    }

    protected function nofollowLinks(LinkStatisticsResult $statistics): int
    {
        return $statistics->internalNofollow;
    }
}

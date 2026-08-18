<?php

namespace TwillSeo\Analysis\Research;

final readonly class LinkStatisticsResult
{
    public function __construct(
        public int $internalTotal,
        public int $internalNofollow,
        public int $externalTotal,
        public int $externalNofollow,
    ) {}
}

<?php

namespace TwillSeo\Analysis\Html;

final readonly class Link
{
    public function __construct(
        public string $href,
        public string $anchorText,
        public bool $isNofollow,
        public LinkScope $scope,
    ) {}
}

<?php

namespace TwillSeo\Analysis\Html;

/**
 * The HTML fragment reduced to the shapes assessments actually ask about.
 * Everything downstream reads this, never the DOM, so the analysis never
 * depends on which parser backend produced it.
 */
final readonly class ParsedContent
{
    /**
     * @param  list<Paragraph>  $paragraphs
     * @param  list<Heading>  $headings
     * @param  list<Image>  $images
     * @param  list<Link>  $links
     * @param  list<HeadingBoundary>  $headingBoundaries  in document order
     */
    public function __construct(
        public string $plainText,
        public array $paragraphs,
        public array $headings,
        public array $images,
        public array $links,
        public array $headingBoundaries = [],
    ) {}

    public static function empty(): self
    {
        return new self('', [], [], [], [], []);
    }

    public function countHeadingsOfLevel(int $level): int
    {
        return count(array_filter($this->headings, fn (Heading $heading) => $heading->level === $level));
    }

    /**
     * @return list<Link>
     */
    public function linksInScope(LinkScope $scope): array
    {
        return array_values(array_filter($this->links, fn (Link $link) => $link->scope === $scope));
    }
}

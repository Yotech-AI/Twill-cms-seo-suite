<?php

namespace TwillSeo\Analysis\Paper;

use DateTimeImmutable;

/**
 * Builds a Paper by name. A caller usually sets three or four of eleven
 * fields, which positional arguments make unreadable and fragile.
 */
final class PaperBuilder
{
    private string $text = '';

    private string $keyword = '';

    /** @var list<string> */
    private array $synonyms = [];

    private string $title = '';

    private ?int $titleWidth = null;

    private string $description = '';

    private string $slug = '';

    private string $permalink = '';

    private string $locale = 'en';

    private ?DateTimeImmutable $date = null;

    /** @var array<string,mixed> */
    private array $customData = [];

    public function text(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function keyword(string $keyword): self
    {
        $this->keyword = $keyword;

        return $this;
    }

    /**
     * @param  list<string>  $synonyms
     */
    public function synonyms(array $synonyms): self
    {
        $this->synonyms = array_values($synonyms);

        return $this;
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function titleWidth(?int $titleWidth): self
    {
        $this->titleWidth = $titleWidth;

        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function slug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function permalink(string $permalink): self
    {
        $this->permalink = $permalink;

        return $this;
    }

    public function locale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function date(?DateTimeImmutable $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @param  array<string,mixed>  $customData
     */
    public function customData(array $customData): self
    {
        $this->customData = $customData;

        return $this;
    }

    public function build(): Paper
    {
        return new Paper(
            $this->text,
            $this->keyword,
            $this->synonyms,
            $this->title,
            $this->titleWidth,
            $this->description,
            $this->slug,
            $this->permalink,
            $this->locale,
            $this->date,
            $this->customData,
        );
    }
}

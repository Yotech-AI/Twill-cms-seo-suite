<?php

namespace TwillSeo\Analysis\Html;

final readonly class Heading
{
    /**
     * @param  int  $level  1-6, as in h1..h6
     */
    public function __construct(public int $level, public string $text) {}
}

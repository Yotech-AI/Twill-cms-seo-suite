<?php

namespace TwillSeo\Analysis\Assessment\Config;

/**
 * One band of the text length scale: everything from $minimumWords up to the
 * next tier scores $score and gets the $branch message.
 */
final readonly class TextLengthTier
{
    public function __construct(
        public int $minimumWords,
        public int $score,
        public string $branch,
    ) {}
}

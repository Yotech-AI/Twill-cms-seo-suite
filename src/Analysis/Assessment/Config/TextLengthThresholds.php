<?php

namespace TwillSeo\Analysis\Assessment\Config;

/**
 * How long a text has to be.
 *
 * Two scales, because cornerstone content is held to a different standard: a
 * 500 word article is a perfectly good post and a poor pillar page. The
 * cornerstone scale is also coarser — there is no point telling the author of
 * a 200 word cornerstone page which flavour of "far too short" they hit.
 */
final readonly class TextLengthThresholds
{
    /**
     * @param  int  $recommended  the length the feedback tells the author to aim for
     * @param  list<TextLengthTier>  $tiers  highest minimum first, ending in a tier with a
     *                                       minimum of zero so every word count matches one
     */
    private function __construct(
        public int $recommended,
        private array $tiers,
    ) {}

    public static function default(): self
    {
        return new self(300, [
            new TextLengthTier(300, 9, 'good'),
            new TextLengthTier(250, 6, 'slightly_short'),
            new TextLengthTier(200, 3, 'short'),
            new TextLengthTier(100, -10, 'very_short'),
            new TextLengthTier(0, -20, 'far_too_short'),
        ]);
    }

    public static function cornerstone(): self
    {
        return new self(900, [
            new TextLengthTier(900, 9, 'good'),
            new TextLengthTier(400, 6, 'slightly_short'),
            new TextLengthTier(0, -20, 'far_too_short'),
        ]);
    }

    public function evaluate(int $words): TextLengthTier
    {
        $match = $this->tiers[count($this->tiers) - 1];

        foreach ($this->tiers as $tier) {
            if ($words >= $tier->minimumWords) {
                $match = $tier;

                break;
            }
        }

        return $match;
    }
}

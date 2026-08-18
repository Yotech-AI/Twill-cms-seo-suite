<?php

namespace TwillSeo\Analysis\Research;

final readonly class TitleWidthResult
{
    /**
     * @param  bool  $estimated  false when the browser measured it, true when it was
     *                           approximated server-side; the panel words its
     *                           feedback differently for the two
     */
    public function __construct(public int $px, public bool $estimated) {}
}

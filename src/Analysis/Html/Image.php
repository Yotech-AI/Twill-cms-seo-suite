<?php

namespace TwillSeo\Analysis\Html;

final readonly class Image
{
    /**
     * @param  string|null  $alt  null when the attribute is absent, '' when it is present
     *                            but empty. The difference matters: an empty alt is a
     *                            deliberate "this image is decorative", a missing one is
     *                            an oversight.
     */
    public function __construct(public string $src, public ?string $alt) {}
}

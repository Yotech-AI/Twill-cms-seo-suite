<?php

namespace TwillSeo\Contracts;

/**
 * Turns a saved model into the HTML PaperFactory feeds the analysis engine as
 * `text`. The default implementation (RenderedBlocksResolver) renders Twill
 * blocks and configured content_fields; a registry entry's `content` key can
 * swap in a host-specific resolver for a model whose real content lives
 * somewhere neither of those covers.
 */
interface SeoContentResolver
{
    public function resolve(object $model, string $locale): ResolvedContent;
}

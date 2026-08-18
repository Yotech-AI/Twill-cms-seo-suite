<?php

namespace TwillSeo\Services\Meta;

/**
 * Assembles the `<meta name="robots">` content string. No framework
 * dependency — pure string assembly, see its own containerless test file.
 *
 * Robots meta is ALWAYS emitted (Yoast parity, not an opt-in feature): the
 * index/follow pair always comes first, then whatever default directives the
 * caller supplies (config('twill-seo.robots.default_directives'), overridable
 * per install via SeoSettings::robotsDefaults()) in the order given.
 */
final class RobotsDirectives
{
    /**
     * @param  list<string>  $defaults
     */
    public function for(bool $noindex, bool $nofollow, array $defaults): string
    {
        return implode(', ', [
            $noindex ? 'noindex' : 'index',
            $nofollow ? 'nofollow' : 'follow',
            ...array_map(strval(...), $defaults),
        ]);
    }
}

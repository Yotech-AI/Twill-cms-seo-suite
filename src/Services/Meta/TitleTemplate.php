<?php

namespace TwillSeo\Services\Meta;

/**
 * Pure string substitution for a Yoast-style title/description template —
 * no framework dependency (see its own containerless test file), since
 * SeoResolver is the only thing that ever calls it and this is plain text
 * transformation, not I/O.
 *
 * Known variables: {title}, {sep}, {site_name}, {tagline}, {page} (v1 always
 * renders '' for {page} — reserved for pagination). Any other `{...}` token
 * is removed rather than left in the output.
 *
 * Collapse rule: a template author writes literal spaces around each token
 * (e.g. "{title} {sep} {site_name}"), so once an empty variable's own
 * surrounding template-text has been substituted, that position can leave
 * behind a doubled separator ("Post -  - Site"), or a leading/trailing one
 * ("- Site" / "Post -") with nothing on the empty side. All three are
 * collapsed down to a clean result ("Post - Site" / "Site" / "Post") by
 * re-tokenizing the substituted string on single spaces and dropping any
 * separator token that is redundant (adjacent to another separator, or at
 * either end). This assumes the conventional "token surrounded by spaces"
 * template shape — every template this package ships or documents follows
 * it — rather than attempting to collapse a separator glued directly to
 * neighboring text with no space at all.
 */
final class TitleTemplate
{
    private const KNOWN_VARS = ['title', 'sep', 'site_name', 'tagline', 'page'];

    /**
     * Anything shaped like `{word}` that survives known-variable
     * substitution — i.e. a token this method does not recognize.
     */
    private const UNKNOWN_TOKEN_PATTERN = '/\{[^{}]*\}/';

    /**
     * @param  array<string,?string>  $vars
     */
    public function render(string $template, array $vars): string
    {
        $rendered = $this->substituteKnownVars($template, $vars);
        $rendered = $this->stripUnknownTokens($rendered);
        $rendered = $this->collapseWhitespace($rendered);

        return $this->collapseSeparator($rendered, trim((string) ($vars['sep'] ?? '')));
    }

    /**
     * @param  array<string,?string>  $vars
     */
    private function substituteKnownVars(string $template, array $vars): string
    {
        $replacements = [];

        foreach (self::KNOWN_VARS as $name) {
            $replacements['{'.$name.'}'] = (string) ($vars[$name] ?? '');
        }

        return strtr($template, $replacements);
    }

    private function stripUnknownTokens(string $rendered): string
    {
        return preg_replace(self::UNKNOWN_TOKEN_PATTERN, '', $rendered) ?? $rendered;
    }

    private function collapseWhitespace(string $rendered): string
    {
        return trim(preg_replace('/\s+/', ' ', $rendered) ?? $rendered);
    }

    /**
     * Re-tokenizes on single spaces (safe post-collapseWhitespace()) and
     * drops any separator token that has nothing meaningful on one side:
     * at the very start, immediately after another separator token, or
     * left dangling at the very end once the loop finishes.
     */
    private function collapseSeparator(string $rendered, string $sep): string
    {
        if ($rendered === '') {
            return '';
        }

        $tokens = explode(' ', $rendered);
        $collapsed = [];

        foreach ($tokens as $token) {
            $isSeparator = $sep !== '' && $token === $sep;

            if ($isSeparator && ($collapsed === [] || end($collapsed) === $sep)) {
                continue;
            }

            $collapsed[] = $token;
        }

        if ($collapsed !== [] && end($collapsed) === $sep) {
            array_pop($collapsed);
        }

        return implode(' ', $collapsed);
    }
}

<?php

namespace TwillSeo\Analysis\Html;

/**
 * The one place that decides what "the same text" means. The parser, the
 * tokenizers and the keyphrase matcher all have to agree on it, or a keyphrase
 * a human can plainly see in the copy will fail to match.
 */
final class TextNormalizer
{
    /**
     * Typographic quotes an editor inserts automatically, folded onto the
     * plain ASCII forms an author types into a keyphrase field.
     */
    private const QUOTE_MAP = [
        "\u{2018}" => "'",
        "\u{2019}" => "'",
        "\u{201A}" => "'",
        "\u{201B}" => "'",
        "\u{2032}" => "'",
        "\u{201C}" => '"',
        "\u{201D}" => '"',
        "\u{201E}" => '"',
        "\u{201F}" => '"',
        "\u{2033}" => '"',
    ];

    /**
     * Spaces that read as whitespace but are not ASCII space. They survive
     * entity decoding (&nbsp; is the common one) and would otherwise glue two
     * words into a single token.
     */
    private const SPACE_LIKE = ["\u{00A0}", "\u{202F}", "\u{2007}"];

    public static function decodeEntities(string $text): string
    {
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function foldQuotes(string $text): string
    {
        return strtr($text, self::QUOTE_MAP);
    }

    public static function collapseWhitespace(string $text): string
    {
        $spaced = str_replace(self::SPACE_LIKE, ' ', $text);

        // preg_replace returns null on invalid UTF-8, which a CMS carrying
        // legacy latin-1 rows really does produce. Falling back to the
        // uncollapsed text keeps the content analysable instead of silently
        // reporting an empty page.
        return trim(preg_replace('/\s+/u', ' ', $spaced) ?? $spaced);
    }

    /**
     * Full normalisation for text that came from outside the DOM (a keyphrase,
     * a title). Text extracted from the DOM must not be decoded a second time,
     * so the parser calls collapseWhitespace() directly.
     */
    public static function fold(string $text): string
    {
        return self::collapseWhitespace(self::foldQuotes(self::decodeEntities($text)));
    }
}

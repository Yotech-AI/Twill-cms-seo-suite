<?php

namespace TwillSeo\Analysis\Html;

use Dom\Element;
use Dom\HTMLDocument;
use Dom\Node;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Throwable;

/**
 * Turns an editor's HTML fragment into ParsedContent.
 *
 * Two backends: PHP 8.4's spec-compliant HTML5 parser where it exists, and
 * libxml's HTML parser below that. Both are normalised into the same
 * ParsedContent here and nowhere else, so no assessment ever has to care which
 * one ran. The node APIs are two disjoint class hierarchies (Dom\Node and
 * DOMNode share no ancestor), hence the union type hints on the walkers.
 */
final class HtmlParser
{
    /**
     * Editor content is a fragment, not a document. Wrapping it gives the walk
     * a single known root regardless of what the parser wrapped around it.
     */
    private const ROOT_ID = '__twillseo_root';

    /** Neither text nor links nor images: these subtrees are not page content. */
    private const EXCLUDED_TAGS = ['script', 'style', 'noscript', 'template', 'svg', 'math'];

    /** Real content, but their text is source code and must not be analysed as prose. */
    private const OPAQUE_TAGS = ['code', 'pre'];

    private const HEADING_TAGS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

    /**
     * Anything not listed here counts as inline, so an unknown or custom
     * element keeps the surrounding sentence in one piece rather than
     * splitting it. br is here only for the space it forces into the text; the
     * paragraph walk handles it separately.
     */
    private const BLOCK_TAGS = [
        'address', 'article', 'aside', 'blockquote', 'br', 'dd', 'details', 'div', 'dl', 'dt',
        'fieldset', 'figcaption', 'figure', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'header', 'hr', 'li', 'main', 'nav', 'ol', 'p', 'pre', 'section', 'summary', 'table',
        'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'ul',
    ];

    /**
     * @param  bool  $forceLegacyBackend  the modern backend is used whenever it exists, which on
     *                                    PHP 8.4 means the legacy path would never run; tests set
     *                                    this to cover both.
     */
    public function __construct(private readonly bool $forceLegacyBackend = false) {}

    public function parse(string $html, string $permalink = ''): ParsedContent
    {
        $root = $this->loadRoot($html);

        if ($root === null) {
            return ParsedContent::empty();
        }

        $paragraphs = [];
        $this->collectParagraphs($root, $paragraphs);

        $headings = [];
        $images = [];
        $links = [];

        foreach ($this->collectElements($root) as $element) {
            $tag = self::tagName($element);

            if (in_array($tag, self::HEADING_TAGS, true)) {
                $headings[] = new Heading((int) substr($tag, 1), $this->textOf($element));
            } elseif ($tag === 'img') {
                $images[] = new Image(
                    self::attribute($element, 'src'),
                    $element->hasAttribute('alt') ? self::attribute($element, 'alt') : null,
                );
            } elseif ($tag === 'a' && $element->hasAttribute('href')) {
                $href = self::attribute($element, 'href');

                $links[] = new Link(
                    $href,
                    $this->textOf($element),
                    self::isNofollow(self::attribute($element, 'rel')),
                    LinkScope::forHref($href, $permalink),
                );
            }
        }

        return new ParsedContent($this->textOf($root), $paragraphs, $headings, $images, $links);
    }

    private function loadRoot(string $html): DOMNode|Node|null
    {
        $wrapped = '<div id="'.self::ROOT_ID.'">'.$html.'</div>';

        try {
            return $this->useModernBackend() ? $this->loadModern($wrapped) : $this->loadLegacy($wrapped);
        } catch (Throwable) {
            // A paper the parser chokes on still has to produce a report, so
            // hostile markup analyses as empty content rather than blowing up
            // the whole run.
            return null;
        }
    }

    private function useModernBackend(): bool
    {
        return ! $this->forceLegacyBackend && class_exists(HTMLDocument::class);
    }

    private function loadModern(string $wrapped): ?Node
    {
        return HTMLDocument::createFromString($wrapped, LIBXML_NOERROR)->getElementById(self::ROOT_ID);
    }

    private function loadLegacy(string $wrapped): ?DOMNode
    {
        // libxml assumes ISO-8859-1 for a document with no charset
        // declaration, so every non-ASCII character is pre-encoded as a
        // numeric entity that the parser then decodes back to UTF-8.
        $encoded = mb_encode_numericentity($wrapped, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');

        $previous = libxml_use_internal_errors(true);

        try {
            $document = new DOMDocument;
            $document->loadHTML($encoded, LIBXML_NOERROR | LIBXML_NONET | LIBXML_HTML_NODEFDTD);

            // getElementById needs a DTD to know which attributes are IDs,
            // which an HTML fragment does not carry.
            return (new DOMXPath($document))->query('//*[@id="'.self::ROOT_ID.'"]')->item(0);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * Every element below $node in document order, skipping subtrees that are
     * not page content.
     *
     * @return list<DOMElement|Element> only elements, so callers may read attributes
     */
    private function collectElements(DOMNode|Node $node): array
    {
        $found = [];

        foreach ($node->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE || in_array(self::tagName($child), self::EXCLUDED_TAGS, true)) {
                continue;
            }

            $found[] = $child;

            foreach ($this->collectElements($child) as $descendant) {
                $found[] = $descendant;
            }
        }

        return $found;
    }

    /**
     * @param  list<Paragraph>  $paragraphs
     */
    private function collectParagraphs(DOMNode|Node $node, array &$paragraphs): void
    {
        $buffer = [];

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $buffer[] = $child;

                continue;
            }

            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $tag = self::tagName($child);

            if (in_array($tag, self::EXCLUDED_TAGS, true) || in_array($tag, self::OPAQUE_TAGS, true)) {
                continue;
            }

            // Headings are their own list, but they still end whatever inline
            // run preceded them.
            if (in_array($tag, self::HEADING_TAGS, true)) {
                $this->flushParagraphs($buffer, $paragraphs);

                continue;
            }

            if ($tag === 'br' || ! in_array($tag, self::BLOCK_TAGS, true)) {
                $buffer[] = $child;

                continue;
            }

            $this->flushParagraphs($buffer, $paragraphs);
            $this->collectParagraphs($child, $paragraphs);
        }

        $this->flushParagraphs($buffer, $paragraphs);
    }

    /**
     * Turns the pending run of inline nodes into paragraphs and empties the
     * buffer. A run of two or more <br> is how an editor writes a paragraph
     * break inside one container, so it splits the run.
     *
     * @param  list<DOMNode|Node>  $buffer
     * @param  list<Paragraph>  $paragraphs
     */
    private function flushParagraphs(array &$buffer, array &$paragraphs): void
    {
        $nodes = $buffer;
        $buffer = [];

        $runs = [[]];
        $breaks = 0;

        foreach ($nodes as $node) {
            if ($node->nodeType === XML_ELEMENT_NODE && self::tagName($node) === 'br') {
                $breaks++;

                if ($breaks === 1) {
                    // A lone break ends a line, not a paragraph — but it is
                    // still a space, or "one<br>two" reads as one word.
                    $runs[count($runs) - 1][] = $node;
                } elseif ($breaks === 2) {
                    // Only the second break splits; a third or fourth changes
                    // nothing, since editors emit runs of them freely.
                    $runs[] = [];
                }

                continue;
            }

            // Whitespace between two <br> keeps them consecutive, but is still
            // kept in the run: it is the only separator between two adjacent
            // inline elements.
            if ($node->nodeType !== XML_TEXT_NODE || trim($node->nodeValue ?? '') !== '') {
                $breaks = 0;
            }

            $runs[count($runs) - 1][] = $node;
        }

        foreach ($runs as $run) {
            $text = '';

            foreach ($run as $node) {
                $text .= $this->nodeText($node);
            }

            $text = TextNormalizer::collapseWhitespace($text);

            if ($text !== '') {
                $paragraphs[] = new Paragraph($text);
            }
        }
    }

    /**
     * The readable text of an element, whitespace collapsed. Entities are
     * already decoded by the DOM, so decoding again here would turn an
     * author's literal "&amp;lt;" into "<".
     */
    private function textOf(DOMNode|Node $node): string
    {
        return TextNormalizer::collapseWhitespace($this->nodeText($node, true));
    }

    /**
     * @param  bool  $asContainer  extract the children of $node rather than $node itself, so a
     *                             block root does not wrap its own text in stray spaces
     */
    private function nodeText(DOMNode|Node $node, bool $asContainer = false): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return $node->nodeValue ?? '';
        }

        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return '';
        }

        if (! $asContainer) {
            $tag = self::tagName($node);

            if (in_array($tag, self::EXCLUDED_TAGS, true) || in_array($tag, self::OPAQUE_TAGS, true)) {
                return '';
            }

            if (in_array($tag, self::BLOCK_TAGS, true)) {
                // A block boundary is a word boundary: without this, two
                // paragraphs would run their last and first word together.
                return ' '.$this->childText($node).' ';
            }
        }

        return $this->childText($node);
    }

    private function childText(DOMNode|Node $node): string
    {
        $text = '';

        foreach ($node->childNodes as $child) {
            $text .= $this->nodeText($child);
        }

        return $text;
    }

    /**
     * The 8.4 DOM returns null for an absent attribute where libxml returns an
     * empty string. Callers get the empty string from both.
     */
    private static function attribute(DOMElement|Element $element, string $name): string
    {
        return (string) ($element->getAttribute($name) ?? '');
    }

    private static function isNofollow(string $rel): bool
    {
        // rel is a space separated token list, but editors do emit commas.
        $tokens = preg_split('/[\s,]+/', strtolower($rel), -1, PREG_SPLIT_NO_EMPTY);

        return in_array('nofollow', $tokens ?: [], true);
    }

    /**
     * The 8.4 DOM reports nodeName uppercase for HTML elements while libxml
     * reports it lowercase; localName is lowercase in both.
     */
    private static function tagName(DOMNode|Node $node): string
    {
        return strtolower((string) $node->localName);
    }
}

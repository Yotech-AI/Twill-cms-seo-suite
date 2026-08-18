<?php

use TwillSeo\Analysis\Html\HtmlParser;
use TwillSeo\Analysis\Html\LinkScope;
use TwillSeo\Analysis\Html\ParsedContent;

/**
 * Local PHP is 8.4, so the modern backend is the one that runs by default and
 * the legacy DOMDocument path would otherwise never be exercised. Every parser
 * expectation therefore runs against both.
 */
dataset('html backends', [
    'modern backend' => [false],
    'legacy backend' => [true],
]);

function htmlFixture(string $name): string
{
    return (string) file_get_contents(dirname(__DIR__, 3).'/Fixtures/html/'.$name.'.html');
}

const FIXTURE_PERMALINK = 'https://example.test/current-page';

function parseFixture(string $name, bool $legacy): ParsedContent
{
    return (new HtmlParser($legacy))->parse(htmlFixture($name), FIXTURE_PERMALINK);
}

it('splits paragraphs by container and by double break', function (bool $legacy) {
    $content = parseFixture('paragraphs', $legacy);

    expect(array_map(fn ($p) => $p->text, $content->paragraphs))->toBe([
        'First paragraph.',
        'Second one',
        'Third one',
        'List item one',
        'List item two',
        'Quoted paragraph.',
        'Caption text',
        'Cell one',
        'Cell two',
        'Inline emphasis and strength stay in one paragraph.',
        // A single break is a line break, not a paragraph break — but it is
        // still a space between the words on either side of it.
        'Line one line two',
        'Three',
        'breaks still split once',
    ]);
})->with('html backends');

it('captures headings with their level in document order', function (bool $legacy) {
    $content = parseFixture('headings', $legacy);

    expect(array_map(fn ($h) => [$h->level, $h->text], $content->headings))->toBe([
        [1, 'Main title'],
        [2, 'Section two'],
        [3, 'Sub section'],
        [1, 'A second H1'],
        [6, 'Deepest'],
    ])->and($content->countHeadingsOfLevel(1))->toBe(2)
        ->and($content->countHeadingsOfLevel(4))->toBe(0);
})->with('html backends');

it('tells a missing alt apart from an empty one', function (bool $legacy) {
    $content = parseFixture('images', $legacy);

    expect(array_map(fn ($i) => [$i->src, $i->alt], $content->images))->toBe([
        ['/no-alt.png', null],
        ['/empty-alt.png', ''],
        ['/with-alt.png', 'A described image'],
    ]);
})->with('html backends');

it('classifies link scope and nofollow', function (bool $legacy) {
    $content = parseFixture('links', $legacy);

    expect(array_map(fn ($l) => [$l->href, $l->scope, $l->isNofollow], $content->links))->toBe([
        ['/internal-relative', LinkScope::Internal, false],
        ['https://example.test/other-page', LinkScope::Internal, false],
        ['https://www.example.test/www-page', LinkScope::Internal, false],
        ['https://external.test/page', LinkScope::External, true],
        ['https://external.test/other', LinkScope::External, false],
        ['#section', LinkScope::Other, false],
        ['mailto:hi@example.test', LinkScope::Other, false],
        ['tel:+3112345678', LinkScope::Other, false],
    ]);
})->with('html backends');

it('keeps the anchor text of every link', function (bool $legacy) {
    $content = parseFixture('links', $legacy);

    expect($content->links[0]->anchorText)->toBe('Relative')
        ->and($content->links[3]->anchorText)->toBe('External nofollow');
})->with('html backends');

it('reads only internal or only external links when asked', function (bool $legacy) {
    $content = parseFixture('links', $legacy);

    expect($content->linksInScope(LinkScope::Internal))->toHaveCount(3)
        ->and($content->linksInScope(LinkScope::External))->toHaveCount(2)
        ->and($content->linksInScope(LinkScope::Other))->toHaveCount(3);
})->with('html backends');

it('treats every link as external when the paper has no permalink', function (bool $legacy) {
    $content = (new HtmlParser($legacy))->parse(htmlFixture('links'), '');

    expect($content->links[1]->scope)->toBe(LinkScope::External)
        ->and($content->links[0]->scope)->toBe(LinkScope::Internal);
})->with('html backends');

it('drops script, style, embedded markup and code from the text', function (bool $legacy) {
    $content = parseFixture('excluded', $legacy);

    expect($content->plainText)->toBe('Visible text. Code sample: Still visible.');
})->with('html backends');

it('recovers paragraphs from an unclosed fragment', function (bool $legacy) {
    $content = parseFixture('broken', $legacy);

    expect(array_map(fn ($p) => $p->text, $content->paragraphs))->toBe(['unclosed', 'text'])
        ->and($content->plainText)->toBe('unclosed text');
})->with('html backends');

it('keeps multibyte characters and decodes entities', function (bool $legacy) {
    $content = parseFixture('utf8', $legacy);

    expect(array_map(fn ($p) => $p->text, $content->paragraphs))->toBe([
        'Café münchen straße — naïve 🚀 déjà vu',
        'Café & co — ok',
    ])->and($content->plainText)->toBe('Café münchen straße — naïve 🚀 déjà vu Café & co — ok');
})->with('html backends');

it('recovers what it can from hostile markup instead of throwing', function (bool $legacy) {
    // Both parsers swallow the never-closed tag and its garbage attributes,
    // leaving only the stray text before it.
    $content = (new HtmlParser($legacy))->parse('<<<>>> <p unclosed attr= <a href=', 'https://example.test/x');

    expect($content->plainText)->toBe('<<<>>>')
        ->and(array_map(fn ($p) => $p->text, $content->paragraphs))->toBe(['<<<>>>'])
        ->and($content->links)->toBe([]);
})->with('html backends');

it('returns empty content for an empty fragment', function (bool $legacy) {
    $content = (new HtmlParser($legacy))->parse('', 'https://example.test/x');

    expect($content->plainText)->toBe('')
        ->and($content->paragraphs)->toBe([])
        ->and($content->headings)->toBe([])
        ->and($content->images)->toBe([])
        ->and($content->links)->toBe([]);
})->with('html backends');

it('parses every fixture identically on both backends', function (string $fixture) {
    $modern = (new HtmlParser(false))->parse(htmlFixture($fixture), FIXTURE_PERMALINK);
    $legacy = (new HtmlParser(true))->parse(htmlFixture($fixture), FIXTURE_PERMALINK);

    expect($legacy)->toEqual($modern);
})->with(['paragraphs', 'headings', 'images', 'links', 'excluded', 'broken', 'utf8']);

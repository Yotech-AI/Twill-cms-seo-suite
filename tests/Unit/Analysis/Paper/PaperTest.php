<?php

namespace TwillSeo\Tests\Unit\Analysis\Paper;

use DateTimeImmutable;
use TwillSeo\Analysis\Language\LanguagePackRegistry;
use TwillSeo\Analysis\Paper\Paper;

it('reduces a locale to its language subtag', function (string $locale, string $expected) {
    expect(Paper::builder()->locale($locale)->build()->languageCode())->toBe($expected);
})->with([
    'already a language' => ['en', 'en'],
    'underscore region' => ['nl_NL', 'nl'],
    'hyphen region' => ['de-DE', 'de'],
    'uppercase' => ['NL', 'nl'],
    'script and region' => ['zh-Hant-TW', 'zh'],
    // No locale means no language, not English. Guessing here would make the
    // report claim a language the pack registry never resolved.
    'nothing at all' => ['', ''],
    'whitespace only' => ['   ', ''],
]);

it('derives exactly the same language code as the pack registry', function (string $locale) {
    // These two derivations decide different things — what the report says the
    // language is, and which pack analyses it — so they must never disagree.
    expect(Paper::builder()->locale($locale)->build()->languageCode())
        ->toBe(LanguagePackRegistry::languageCode($locale));
})->with(['en', 'en_GB', 'nl_NL', 'de-DE', 'NL', 'zh-Hant-TW', '', '   ', '_', 'x']);

it('knows which of its fields are filled in', function () {
    $empty = Paper::builder()->build();
    $full = Paper::builder()
        ->text('<p>Words</p>')->keyword('twill seo')->title('A title')
        ->description('A description')->slug('a-slug')->build();

    expect([$empty->hasText(), $empty->hasKeyword(), $empty->hasTitle(), $empty->hasDescription(), $empty->hasSlug()])
        ->toBe([false, false, false, false, false])
        ->and([$full->hasText(), $full->hasKeyword(), $full->hasTitle(), $full->hasDescription(), $full->hasSlug()])
        ->toBe([true, true, true, true, true]);
});

it('treats a whitespace only field as empty', function () {
    $paper = Paper::builder()->text("  \n ")->keyword('  ')->title(' ')->description(' ')->slug(' ')->build();

    expect([$paper->hasText(), $paper->hasKeyword(), $paper->hasTitle(), $paper->hasDescription(), $paper->hasSlug()])
        ->toBe([false, false, false, false, false]);
});

it('carries everything the builder was given', function () {
    $date = new DateTimeImmutable('2026-01-02 03:04:05');

    $paper = Paper::builder()
        ->text('<p>Body</p>')
        ->keyword('twill seo')
        ->synonyms(['twill search', 'twill optimisation'])
        ->title('A title')
        ->titleWidth(432)
        ->description('A description')
        ->slug('a-slug')
        ->permalink('https://example.test/a-slug')
        ->locale('nl_NL')
        ->date($date)
        ->customData(['source' => 'test'])
        ->build();

    expect($paper->text)->toBe('<p>Body</p>')
        ->and($paper->keyword)->toBe('twill seo')
        ->and($paper->synonyms)->toBe(['twill search', 'twill optimisation'])
        ->and($paper->title)->toBe('A title')
        ->and($paper->titleWidth)->toBe(432)
        ->and($paper->description)->toBe('A description')
        ->and($paper->slug)->toBe('a-slug')
        ->and($paper->permalink)->toBe('https://example.test/a-slug')
        ->and($paper->locale)->toBe('nl_NL')
        ->and($paper->date)->toBe($date)
        ->and($paper->customData)->toBe(['source' => 'test']);
});

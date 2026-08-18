<?php

namespace TwillSeo\Tests\Unit\Analysis\Language;

use TwillSeo\Analysis\Language\WordList;

it('matches membership without regard to case', function () {
    $list = WordList::fromArray(['The', 'a', 'ÉÉN']);

    expect($list->contains('the'))->toBeTrue()
        ->and($list->contains('THE'))->toBeTrue()
        ->and($list->contains('één'))->toBeTrue()
        ->and($list->contains('dog'))->toBeFalse()
        ->and($list->isEmpty())->toBeFalse();
});

it('is empty when built from nothing', function () {
    expect(WordList::empty()->isEmpty())->toBeTrue()
        ->and(WordList::empty()->contains('the'))->toBeFalse()
        ->and(WordList::fromArray([])->isEmpty())->toBeTrue();
});

it('removes its members from a list of words and reindexes', function () {
    $list = WordList::fromArray(['the', 'a', 'of']);

    expect($list->filter(['The', 'best', 'of', 'a', 'dog']))->toBe(['best', 'dog']);
});

it('removes nothing when the list is empty', function () {
    expect(WordList::empty()->filter(['the', 'dog']))->toBe(['the', 'dog']);
});

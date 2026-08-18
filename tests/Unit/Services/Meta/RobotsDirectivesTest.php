<?php

namespace TwillSeo\Tests\Unit\Services\Meta;

use TwillSeo\Services\Meta\RobotsDirectives;

/**
 * Containerless: pure string assembly, no framework dependency.
 */
beforeEach(function () {
    $this->robots = new RobotsDirectives;
});

it('assembles index/follow plus the configured defaults', function (bool $noindex, bool $nofollow, string $expectedLeadPair) {
    $result = $this->robots->for($noindex, $nofollow, ['max-snippet:-1', 'max-image-preview:large', 'max-video-preview:-1']);

    expect($result)->toBe("{$expectedLeadPair}, max-snippet:-1, max-image-preview:large, max-video-preview:-1");
})->with([
    'index, follow' => [false, false, 'index, follow'],
    'noindex, follow' => [true, false, 'noindex, follow'],
    'index, nofollow' => [false, true, 'index, nofollow'],
    'noindex, nofollow' => [true, true, 'noindex, nofollow'],
]);

it('merges whatever default directives it is given, not a hardcoded list', function () {
    $result = $this->robots->for(false, false, ['max-snippet:20', 'noimageindex']);

    expect($result)->toBe('index, follow, max-snippet:20, noimageindex');
});

it('produces just the index/follow pair with no trailing punctuation when defaults are empty', function () {
    expect($this->robots->for(false, false, []))->toBe('index, follow')
        ->and($this->robots->for(true, true, []))->toBe('noindex, nofollow');
});

it('preserves the order defaults were given in', function () {
    $result = $this->robots->for(false, false, ['b', 'a', 'c']);

    expect($result)->toBe('index, follow, b, a, c');
});

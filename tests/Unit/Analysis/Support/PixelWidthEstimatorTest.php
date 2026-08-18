<?php

namespace TwillSeo\Tests\Unit\Analysis\Support;

use TwillSeo\Analysis\Support\PixelWidthEstimator;

/*
 * Range assertions on purpose. The estimator approximates one font at one size
 * and the browser measurement always wins, so pinning exact pixel counts would
 * only make the table brittle without making the estimate any better.
 */

it('measures nothing as nothing', function () {
    expect(PixelWidthEstimator::estimate(''))->toBe(0);
});

it('makes wide characters wider than narrow ones', function () {
    expect(PixelWidthEstimator::estimate('WWW'))->toBeGreaterThan(PixelWidthEstimator::estimate('iii'))
        ->and(PixelWidthEstimator::estimate('mmm'))->toBeGreaterThan(PixelWidthEstimator::estimate('lll'))
        ->and(PixelWidthEstimator::estimate('AAA'))->toBeGreaterThan(PixelWidthEstimator::estimate('aaa'));
});

it('grows with the length of the text', function () {
    expect(PixelWidthEstimator::estimate('aaaa'))->toBeGreaterThan(PixelWidthEstimator::estimate('aaa'));
});

it('puts a typical forty character title in the range a search result shows', function () {
    // 39 characters of ordinary mixed-case English, which should land well
    // inside the 600px a search result gives a title.
    expect(PixelWidthEstimator::estimate('The complete guide to Twill CMS and SEO'))
        ->toBeGreaterThan(300)
        ->toBeLessThan(450);
});

it('flags an overlong title as over the limit', function () {
    expect(PixelWidthEstimator::estimate('The complete and utterly exhaustive guide to Twill CMS, SEO and everything else'))
        ->toBeGreaterThan(600);
});

it('treats full width characters as wider than latin ones', function () {
    expect(PixelWidthEstimator::estimate('日本語'))->toBeGreaterThan(PixelWidthEstimator::estimate('abc'));
});

it('gives a combining mark no width of its own', function () {
    expect(PixelWidthEstimator::estimate("e\u{0301}"))->toBe(PixelWidthEstimator::estimate('e'));
});

it('measures a space as narrower than a letter', function () {
    expect(PixelWidthEstimator::estimate(' '))->toBeLessThan(PixelWidthEstimator::estimate('a'))
        ->and(PixelWidthEstimator::estimate(' '))->toBeGreaterThan(0);
});

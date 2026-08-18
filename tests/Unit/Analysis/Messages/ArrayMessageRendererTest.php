<?php

use TwillSeo\Analysis\Messages\ArrayMessageRenderer;

it('renders a namespaced key from the package language file', function () {
    expect((new ArrayMessageRenderer)->render('twill-seo::analysis.images.good', []))
        ->toBe('The text is illustrated with at least one image.');
});

it('renders the same key without the namespace', function () {
    expect((new ArrayMessageRenderer)->render('analysis.images.good', []))
        ->toBe('The text is illustrated with at least one image.');
});

it('replaces every named placeholder', function () {
    expect((new ArrayMessageRenderer)->render('twill-seo::analysis.single_h1.multiple', ['count' => 3]))
        ->toBe('The text contains 3 H1 headings. Keep one H1 for the page title and demote the rest to H2 or lower.');
});

it('returns the key itself when there is no message for it', function (string $key) {
    expect((new ArrayMessageRenderer)->render($key, ['count' => 1]))->toBe($key);
})->with([
    'unknown branch' => ['twill-seo::analysis.images.nonexistent'],
    'unknown group' => ['twill-seo::analysis.no_such_group.good'],
    'unknown file' => ['twill-seo::nosuchfile.images.good'],
    'a group is not a message' => ['twill-seo::analysis.images'],
    'too deep' => ['twill-seo::analysis.images.good.deeper'],
    'no key at all' => [''],
]);

it('never reaches outside its language directory', function () {
    expect((new ArrayMessageRenderer)->render('twill-seo::../../../composer.json.images.good', []))
        ->toBe('twill-seo::../../../composer.json.images.good');
});

it('renders placeholders it was given values of any scalar type', function () {
    $renderer = new ArrayMessageRenderer(dirname(__DIR__, 3).'/Fixtures/lang');

    expect($renderer->render('messages.params', ['who' => 'world', 'count' => 3, 'flag' => true, 'missing' => null]))
        ->toBe('world 3 true  done');
});

it('leaves a placeholder alone when no value was given for it', function () {
    $renderer = new ArrayMessageRenderer(dirname(__DIR__, 3).'/Fixtures/lang');

    expect($renderer->render('messages.params', []))
        ->toBe(':who :count :flag :missing done');
});

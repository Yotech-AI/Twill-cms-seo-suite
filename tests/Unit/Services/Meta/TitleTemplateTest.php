<?php

namespace TwillSeo\Tests\Unit\Services\Meta;

use TwillSeo\Services\Meta\TitleTemplate;

/**
 * Containerless: TitleTemplate is pure string manipulation with no framework
 * dependency, so this file never boots Testbench (see tests/Pest.php — only
 * tests/Feature gets the Laravel TestCase).
 */
beforeEach(function () {
    $this->template = new TitleTemplate;
});

it('substitutes every known variable', function () {
    $result = $this->template->render('{title} {sep} {site_name} {sep} {tagline} {sep} {page}', [
        'title' => 'Post',
        'sep' => '-',
        'site_name' => 'Site',
        'tagline' => 'Tagline',
        'page' => '2',
    ]);

    expect($result)->toBe('Post - Site - Tagline - 2');
});

it('renders the documented default template', function () {
    $result = $this->template->render('{title} {sep} {site_name}', [
        'title' => 'My Post',
        'sep' => '-',
        'site_name' => 'My Site',
        'tagline' => '',
        'page' => '',
    ]);

    expect($result)->toBe('My Post - My Site');
});

it('removes a token that is not one of the known variables', function () {
    $result = $this->template->render('{title} {sep} {unknown_var} {site_name}', [
        'title' => 'Post',
        'sep' => '-',
        'site_name' => 'Site',
    ]);

    expect($result)->not->toContain('unknown_var')
        ->not->toContain('{')
        ->toBe('Post - Site');
});

it('collapses the double separator an empty middle variable would otherwise leave', function () {
    // The brief's own worked example: "Post -  - Site" -> "Post - Site".
    $result = $this->template->render('{title} {sep} {tagline} {sep} {site_name}', [
        'title' => 'Post',
        'sep' => '-',
        'tagline' => '',
        'site_name' => 'Site',
    ]);

    expect($result)->toBe('Post - Site');
});

it('drops a leading separator entirely when the variable before it is empty', function () {
    $result = $this->template->render('{title} {sep} {site_name}', [
        'title' => '',
        'sep' => '-',
        'site_name' => 'Site',
    ]);

    expect($result)->toBe('Site');
});

it('drops a trailing separator entirely when the variable after it is empty', function () {
    $result = $this->template->render('{title} {sep} {site_name}', [
        'title' => 'Post',
        'sep' => '-',
        'site_name' => '',
    ]);

    expect($result)->toBe('Post');
});

it('collapses to an empty string when every variable is empty', function () {
    $result = $this->template->render('{title} {sep} {site_name}', [
        'title' => '',
        'sep' => '-',
        'site_name' => '',
    ]);

    expect($result)->toBe('');
});

it('collapses correctly with a custom, non-dash separator', function (string $sep) {
    $result = $this->template->render('{title} {sep} {tagline} {sep} {site_name}', [
        'title' => 'Post',
        'sep' => $sep,
        'tagline' => '',
        'site_name' => 'Site',
    ]);

    expect($result)->toBe("Post {$sep} Site");
})->with([
    'pipe' => ['|'],
    'em dash' => ['—'],
    'double colon' => ['::'],
]);

it('collapses runs of incidental whitespace left by substitution', function () {
    $result = $this->template->render('{title}   {sep}   {site_name}', [
        'title' => 'Post',
        'sep' => '-',
        'site_name' => 'Site',
    ]);

    expect($result)->toBe('Post - Site');
});

it('treats a missing vars key the same as an explicitly empty one', function () {
    // No 'tagline' or 'page' key at all in $vars, not even set to ''.
    $result = $this->template->render('{title} {sep} {tagline}{page} {site_name}', [
        'title' => 'Post',
        'sep' => '-',
        'site_name' => 'Site',
    ]);

    expect($result)->toBe('Post - Site');
});

it('leaves an ordinary template with no variables at all untouched but trimmed', function () {
    expect($this->template->render('Just a static title', []))->toBe('Just a static title');
});

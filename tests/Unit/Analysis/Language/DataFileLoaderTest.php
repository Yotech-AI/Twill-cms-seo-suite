<?php

namespace TwillSeo\Tests\Unit\Analysis\Language;

use TwillSeo\Analysis\Language\Data\DataFileLoader;

it('loads an array from a php file', function () {
    $path = tempnam(sys_get_temp_dir(), 'twillseo').'.php';
    file_put_contents($path, '<?php return ["words" => ["the", "a"]];');

    try {
        expect(DataFileLoader::load($path))->toBe(['words' => ['the', 'a']]);
    } finally {
        @unlink($path);
    }
});

it('keeps serving a file it already read', function () {
    $path = tempnam(sys_get_temp_dir(), 'twillseo').'.php';
    file_put_contents($path, '<?php return ["cached" => true];');

    DataFileLoader::load($path);
    unlink($path);

    // Word lists are read once per process and reused for every paper, so a
    // second analysis must not pay for the file again.
    expect(DataFileLoader::load($path))->toBe(['cached' => true]);
});

it('returns nothing for a file that is missing or does not return an array', function () {
    $notAnArray = tempnam(sys_get_temp_dir(), 'twillseo').'.php';
    file_put_contents($notAnArray, '<?php return "oops";');

    try {
        expect(DataFileLoader::load(sys_get_temp_dir().'/twillseo-does-not-exist.php'))->toBe([])
            ->and(DataFileLoader::load($notAnArray))->toBe([]);
    } finally {
        @unlink($notAnArray);
    }
});

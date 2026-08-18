<?php

namespace TwillSeo\Tests\Unit\Analysis;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * The containerless guarantee, enforced. The analysis engine is a pure-PHP
 * library that a non-Laravel host (or a queued worker with no container) must
 * be able to run, so a single framework import anywhere under src/Analysis is
 * a defect, not a style issue.
 *
 * The pattern allows leading whitespace, because a braced namespace indents its
 * imports, and covers the function and const import forms, which pull in
 * framework code just as effectively as a class import does.
 */
const FRAMEWORK_IMPORT = '/^\s*use\s+(function\s+|const\s+)?(Illuminate|Laravel|A17)\\\\/m';

/**
 * @return list<string>
 */
function analysisSourceFiles(): array
{
    $root = dirname(__DIR__, 3).'/src/Analysis';

    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    sort($files);

    return $files;
}

it('catches every shape of framework import', function (string $source) {
    // Without this the scan below could quietly stop catching anything: a
    // pattern that matches nothing passes just as silently as a clean engine.
    expect(preg_match(FRAMEWORK_IMPORT, $source))->toBe(1);
})->with([
    'a class import' => ["<?php\n\nuse Illuminate\\Support\\Str;\n"],
    'a function import' => ["<?php\n\nuse function Illuminate\\Support\\collect;\n"],
    'a const import' => ["<?php\n\nuse const Illuminate\\Foundation\\Application::VERSION;\n"],
    'an indented import inside a braced namespace' => ["<?php\n\nnamespace X {\n    use A17\\Twill\\Models\\Model;\n}\n"],
    'a first party laravel package' => ["<?php\n\nuse Laravel\\Octane\\Facades\\Octane;\n"],
    'a trait pulled into a class' => ["<?php\n\nclass A\n{\n    use Illuminate\\Foundation\\Auth\\Access\\AuthorizesRequests;\n}\n"],
    'extra whitespace after the keyword' => ["<?php\n\nuse   Illuminate\\Support\\Str;\n"],
]);

it('leaves imports that are not framework imports alone', function (string $source) {
    expect(preg_match(FRAMEWORK_IMPORT, $source))->toBe(0);
})->with([
    'the package own namespace' => ["<?php\n\nuse TwillSeo\\Analysis\\Html\\HtmlParser;\n"],
    'a php core class' => ["<?php\n\nuse DOMDocument;\n"],
    'a trait with no namespace' => ["<?php\n\nclass A\n{\n    use SomeLocalTrait;\n}\n"],
    'a commented out import' => ["<?php\n\n// use Illuminate\\Support\\Str;\n"],
    'a namespace that merely starts the same way' => ["<?php\n\nuse IlluminateLike\\Support\\Str;\n"],
]);

it('ships analysis source files for the guard to scan', function () {
    // Without this the import scan below would pass vacuously if the engine
    // ever moved out of src/Analysis.
    expect(analysisSourceFiles())->not->toBeEmpty();
});

it('imports no framework namespace anywhere under src/Analysis', function () {
    $offenders = [];

    foreach (analysisSourceFiles() as $file) {
        if (preg_match(FRAMEWORK_IMPORT, (string) file_get_contents($file))) {
            $offenders[] = $file;
        }
    }

    expect($offenders)->toBe([]);
});

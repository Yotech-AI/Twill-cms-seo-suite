<?php

/**
 * The containerless guarantee, enforced. The analysis engine is a pure-PHP
 * library that a non-Laravel host (or a queued worker with no container) must
 * be able to run, so a single framework import anywhere under src/Analysis is
 * a defect — not a style issue.
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

it('ships analysis source files for the guard to scan', function () {
    // Without this the import scan below would pass vacuously if the engine
    // ever moved out of src/Analysis.
    expect(analysisSourceFiles())->not->toBeEmpty();
});

it('imports no framework namespace anywhere under src/Analysis', function () {
    $offenders = [];

    foreach (analysisSourceFiles() as $file) {
        if (preg_match('/^use (Illuminate|Laravel|A17)\\\\/m', (string) file_get_contents($file))) {
            $offenders[] = $file;
        }
    }

    expect($offenders)->toBe([]);
});

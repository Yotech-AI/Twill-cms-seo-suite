<?php

namespace TwillSeo\Tests\Unit\Analysis\Assessment\Readability;

use TwillSeo\Analysis\Assessment\Readability\TextPresenceAssessment;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

function textPresenceApplies(string $html): bool
{
    return (new TextPresenceAssessment)->isApplicable(AnalysisFactory::context(
        Paper::builder()->text($html)->build()
    ));
}

it('only speaks up when there is barely any text', function (string $html, bool $applicable) {
    expect(textPresenceApplies($html))->toBe($applicable);
})->with([
    'nothing at all' => ['', true],
    'markup with no words in it' => ['<p></p><div></div>', true],
    'a handful of words' => ['<p>Far too little to judge.</p>', true],
    'one character short of enough' => ['<p>'.str_repeat('a', 49).'</p>', true],
    'exactly enough' => ['<p>'.str_repeat('a', 50).'</p>', false],
    'a real text' => ['<p>'.str_repeat('word ', 40).'</p>', false],
]);

it('measures the text rather than the markup', function () {
    // Plenty of bytes, almost no reading matter.
    expect(textPresenceApplies('<div class="a-very-long-class-name-indeed"><span>Hi</span></div>'))->toBeTrue();
});

it('reports too little text as a failure', function () {
    $result = (new TextPresenceAssessment)->assess(AnalysisFactory::context(Paper::builder()->build()));

    expect($result->score)->toBe(3)
        ->and($result->messageKey)->toBe('twill-seo::analysis.text_presence.too_little')
        ->and($result->text)->toBe(
            'There is not enough text on this page to judge how it reads. Write a few paragraphs and '
            .'the readability analysis will have something to work with.'
        );
});

it('identifies itself as textPresence', function () {
    expect((new TextPresenceAssessment)->identifier())->toBe('textPresence');
});

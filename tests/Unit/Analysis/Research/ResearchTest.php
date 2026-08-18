<?php

use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Analysis\Research\EstimatedTitleWidth;
use TwillSeo\Analysis\Research\LinkStatistics;
use TwillSeo\Analysis\Research\Sentences;
use TwillSeo\Analysis\Research\WordCount;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

it('counts the words of the text and not of its markup', function () {
    $context = AnalysisFactory::context(
        Paper::builder()->text('<p>One two three</p><script>four five six seven</script><p>four</p>')->build()
    );

    expect($context->research(WordCount::class))->toBe(4);
});

it('splits the text into sentences across paragraphs', function () {
    $context = AnalysisFactory::context(
        Paper::builder()->text('<p>First one. Second one!</p><p>Third one?</p>')->build()
    );

    expect($context->research(Sentences::class))->toBe(['First one.', 'Second one!', 'Third one?']);
});

it('counts links by scope and follow state', function () {
    $context = AnalysisFactory::context(
        Paper::builder()
            ->permalink('https://example.test/page')
            ->text(
                '<p><a href="/one">a</a><a href="/two" rel="nofollow">b</a>'
                .'<a href="https://other.test/x">c</a><a href="https://other.test/y" rel="nofollow">d</a>'
                .'<a href="https://other.test/z" rel="nofollow">e</a><a href="#skip">f</a></p>'
            )
            ->build()
    );

    $statistics = $context->research(LinkStatistics::class);

    expect($statistics->internalTotal)->toBe(2)
        ->and($statistics->internalNofollow)->toBe(1)
        ->and($statistics->externalTotal)->toBe(3)
        ->and($statistics->externalNofollow)->toBe(2);
});

it('prefers the width the browser measured', function () {
    $context = AnalysisFactory::context(
        Paper::builder()->title('A title')->titleWidth(432)->build()
    );

    $width = $context->research(EstimatedTitleWidth::class);

    expect($width->px)->toBe(432)
        ->and($width->estimated)->toBeFalse();
});

it('estimates the width when the browser measured nothing', function () {
    $context = AnalysisFactory::context(
        Paper::builder()->title('The complete guide to Twill CMS and SEO')->build()
    );

    $width = $context->research(EstimatedTitleWidth::class);

    expect($width->px)->toBeGreaterThan(300)
        ->and($width->estimated)->toBeTrue();
});

it('measures a title of nothing but whitespace as no title at all', function () {
    $context = AnalysisFactory::context(Paper::builder()->title("  \n ")->build());

    expect($context->research(EstimatedTitleWidth::class)->px)->toBe(0);
});

<?php

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Context\AnalysisContext;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;
use TwillSeo\Tests\Unit\Analysis\Support\CountingResearch;

it('runs a research once however many assessments ask for it', function () {
    CountingResearch::$runs = 0;
    $context = AnalysisFactory::context(Paper::builder()->text('<p>Some words here.</p>')->build());

    expect($context->research(CountingResearch::class))->toBe(1)
        ->and($context->research(CountingResearch::class))->toBe(1)
        ->and(CountingResearch::$runs)->toBe(1);
});

it('gives each analysis its own memo', function () {
    CountingResearch::$runs = 0;
    $paper = Paper::builder()->text('<p>Some words here.</p>')->build();

    AnalysisFactory::context($paper)->research(CountingResearch::class);
    AnalysisFactory::context($paper)->research(CountingResearch::class);

    expect(CountingResearch::$runs)->toBe(2);
});

it('derives the message key from the assessment identifier', function () {
    $context = AnalysisFactory::context(Paper::builder()->build());

    $result = $context->result(fakeAssessment('metaDescriptionLength'), 9, 'good', ['length' => 130, 'max' => 156]);

    expect($result->messageKey)->toBe('twill-seo::analysis.meta_description_length.good')
        ->and($result->identifier)->toBe('metaDescriptionLength')
        ->and($result->messageParams)->toBe(['length' => 130, 'max' => 156]);
});

it('snake cases an identifier that ends in a digit', function () {
    $context = AnalysisFactory::context(Paper::builder()->build());

    expect($context->result(fakeAssessment('singleH1'), 8, 'good')->messageKey)
        ->toBe('twill-seo::analysis.single_h1.good');
});

it('renders the message text through the message renderer', function () {
    $context = AnalysisFactory::context(Paper::builder()->build());

    $result = $context->result(fakeAssessment('metaDescriptionLength'), 9, 'good', ['length' => 130, 'max' => 156]);

    expect($result->text)->toBe('The meta description is 130 characters, which fits the space a search result gives it.');
});

it('falls back to the key itself for a branch with no message', function () {
    $context = AnalysisFactory::context(Paper::builder()->build());

    expect($context->result(fakeAssessment('textLength'), 9, 'no_such_branch')->text)
        ->toBe('twill-seo::analysis.text_length.no_such_branch');
});

function fakeAssessment(string $identifier): Assessment
{
    return new class($identifier) implements Assessment
    {
        public function __construct(private readonly string $identifier) {}

        public function identifier(): string
        {
            return $this->identifier;
        }

        public function isApplicable(AnalysisContext $context): bool
        {
            return true;
        }

        public function assess(AnalysisContext $context): AssessmentResult
        {
            return $context->result($this, 9, 'good');
        }
    };
}

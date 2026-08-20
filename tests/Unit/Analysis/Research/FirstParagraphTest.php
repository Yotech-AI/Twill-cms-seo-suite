<?php

namespace TwillSeo\Tests\Unit\Analysis\Research;

use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Analysis\Research\FirstParagraph;
use TwillSeo\Tests\Unit\Analysis\Support\AnalysisFactory;

/*
 * Regression from the first rendered-template host: hero templates emit tiny
 * UI fragments (an eyebrow label, a CTA button) as paragraphs BEFORE the real
 * introduction, and the introduction check judged "Diensten" instead of the
 * opening alinea. The first PROSE paragraph is the introduction.
 */

function firstParagraphOf(string $html): array
{
    $paper = Paper::builder()->text($html)->keyword('ai')->locale('nl')->build();

    return (new FirstParagraph)->run(AnalysisFactory::context($paper));
}

it('skips leading label fragments and finds the first prose paragraph', function () {
    $sentences = firstParagraphOf(
        '<div>Diensten</div>'
        .'<p>Mistral of Gemini in jouw workflow. Wij bouwen de AI-integraties, jij houdt de regie.</p>'
    );

    expect(implode(' ', $sentences))->toContain('AI-integraties')
        ->and(implode(' ', $sentences))->not->toContain('Diensten');
});

it('skips multiple fragments in a row, CTA buttons included', function () {
    $sentences = firstParagraphOf(
        '<div>Diensten</div><div>Plan een call</div>'
        .'<p>De echte introductie staat hier en gaat over AI-integraties in de praktijk.</p>'
    );

    expect(implode(' ', $sentences))->toContain('echte introductie');
});

it('accepts a short paragraph as prose when it carries sentence punctuation', function () {
    $sentences = firstParagraphOf('<p>Kort. Maar echt.</p><p>Daarna pas de rest van de tekst.</p>');

    expect(implode(' ', $sentences))->toBe('Kort. Maar echt.');
});

it('accepts a long unpunctuated paragraph as prose via the word floor', function () {
    $sentences = firstParagraphOf(
        '<div>een lange rij van tien of meer woorden zonder leesteken erin dus prima proza</div>'
        .'<p>Volgende alinea.</p>'
    );

    expect(implode(' ', $sentences))->toContain('lange rij');
});

it('falls back to the first paragraph when the text is only fragments', function () {
    $sentences = firstParagraphOf('<div>Diensten</div><div>Contact</div>');

    expect($sentences)->toBe(['Diensten']);
});

it('still returns empty for an empty text', function () {
    expect(firstParagraphOf(''))->toBe([]);
});

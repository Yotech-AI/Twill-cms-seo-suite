<?php

namespace TwillSeo\Tests\Fixtures\Blocks;

use A17\Twill\Services\Forms\Fields\Wysiwyg;
use A17\Twill\Services\Forms\Form;
use A17\Twill\View\Components\Blocks\TwillBlockComponent;
use Illuminate\Contracts\View\View;

/**
 * The only block Task 5's tests need: one translatable HTML field, rendered
 * verbatim. Real enough that RenderedBlocksResolver has actual markup to feed
 * the analysis engine (paragraphs, a keyphrase placed on purpose), thin enough
 * that it exercises nothing about Twill's form pipeline — see how the
 * twill-cms-ai-assistent sibling registers its own fixture blocks
 * (tests/Fixtures/Blocks/*.php there) for the pattern this mirrors.
 */
class FixtureContentBlock extends TwillBlockComponent
{
    public function getForm(): Form
    {
        return Form::make([
            Wysiwyg::make()->name('text')->label('Text')->translatable(),
        ]);
    }

    public static function getBlockIdentifier(): string
    {
        return 'fixture-content';
    }

    public static function getBlockTitle(): string
    {
        return 'Fixture Content';
    }

    public static function getBlockGroup(): string
    {
        return 'fixture';
    }

    public function render(): View
    {
        return view('twill-seo-fixtures::content-block');
    }
}

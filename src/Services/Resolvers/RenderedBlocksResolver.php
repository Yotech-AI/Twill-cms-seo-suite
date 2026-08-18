<?php

namespace TwillSeo\Services\Resolvers;

use A17\Twill\Helpers\BlockRenderer;
use A17\Twill\Models\Behaviors\HasBlocks;
use A17\Twill\Models\Block;
use Throwable;
use TwillSeo\Contracts\ResolvedContent;
use TwillSeo\Contracts\SeoContentResolver;
use TwillSeo\Services\ModelRegistry;
use TwillSeo\Support\TranslatedAttribute;

/**
 * The default SeoContentResolver: renders a model's default-editor blocks and
 * appends its configured content_fields, so PaperFactory has something real
 * to analyze even for a model with no dedicated resolver of its own.
 */
final class RenderedBlocksResolver implements SeoContentResolver
{
    private const EDITOR_NAME = 'default';

    public function __construct(private readonly ModelRegistry $registry) {}

    public function resolve(object $model, string $locale): ResolvedContent
    {
        // Translated block fields and translated content_fields both read
        // through app()->getLocale() (Block::translatedInput(),
        // Astrotomic's translate()), so the whole resolution happens under
        // the target locale — restored afterwards, since this runs inside a
        // request whose ambient locale belongs to the admin, not the paper.
        $previousLocale = app()->getLocale();
        app()->setLocale($locale);

        try {
            $blocksHtml = $this->renderBlocks($model);
            $fieldsHtml = $this->renderContentFields($model, $locale);
        } finally {
            app()->setLocale($previousLocale);
        }

        $hasBlocks = trim($blocksHtml) !== '';
        $hasFields = trim($fieldsHtml) !== '';

        $source = match (true) {
            $hasBlocks && $hasFields => 'mixed',
            $hasBlocks => 'rendered_blocks',
            $hasFields => 'content_fields',
            default => 'empty',
        };

        return new ResolvedContent($blocksHtml.$fieldsHtml, $source);
    }

    /**
     * Renders each root block of the default editor independently, so one
     * block that fails to render (an unregistered type, a broken component)
     * degrades that one block rather than losing every other block's text
     * along with it — see BlockRenderer::fromEditor(), which this mirrors
     * but per block instead of for the whole editor at once.
     */
    private function renderBlocks(object $model): string
    {
        if (! in_array(HasBlocks::class, class_uses_recursive($model), true)) {
            return '';
        }

        /** @var iterable<Block> $rootBlocks */
        $rootBlocks = $model->blocks
            ->where('editor_name', self::EDITOR_NAME)
            ->whereNull('parent_id');

        $html = '';

        foreach ($rootBlocks as $block) {
            try {
                $nested = BlockRenderer::getNestedBlocksForBlock($block, $model, self::EDITOR_NAME);
                $html .= (new BlockRenderer([$nested]))->render();
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $html;
    }

    /**
     * Each configured content_fields attribute, translated for $locale and
     * wrapped as its own paragraph — plain text becoming an HTML fragment, so
     * it is escaped rather than trusted the way block/WYSIWYG content is.
     */
    private function renderContentFields(object $model, string $locale): string
    {
        $key = $this->registry->keyFor($model);
        $fields = $key !== null ? $this->registry->get($key)['content_fields'] : [];

        $html = '';

        foreach ($fields as $field) {
            try {
                $value = TranslatedAttribute::get($model, $field, $locale);
            } catch (Throwable $e) {
                report($e);

                continue;
            }

            if ($value !== null && trim($value) !== '') {
                $html .= '<p>'.e($value).'</p>';
            }
        }

        return $html;
    }
}

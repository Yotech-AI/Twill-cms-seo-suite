<?php

namespace TwillSeo\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use TwillSeo\Analysis\AnalysisRunner;
use TwillSeo\Http\Requests\AnalyzeRequest;
use TwillSeo\Services\ModelRegistry;
use TwillSeo\Services\PaperFactory;
use TwillSeo\Services\Settings\SeoSettings;

/**
 * POST /seo/analyze — the debounced, per-keystroke analysis endpoint the
 * editor panel calls. `fields` is optional: with none posted this is exactly
 * the saved-mode analysis ScoreCache would have cached, since both go
 * through the same PaperFactory::fromModel() path.
 */
class AnalyzeController extends Controller
{
    public function __construct(
        private readonly SeoSettings $settings,
        private readonly ModelRegistry $registry,
        private readonly PaperFactory $papers,
        private readonly AnalysisRunner $runner,
    ) {}

    public function __invoke(AnalyzeRequest $request): JsonResponse
    {
        // Same DB-over-config feature gate every other public/admin entry
        // point in this package checks, and the same 404-when-off shape
        // SitemapController uses — the analysis feature has no separate
        // "read only" mode, so a disabled feature means this endpoint does
        // not exist at all rather than answering with an empty report.
        if (! $this->settings->feature('analysis')) {
            abort(404);
        }

        $validated = $request->validated();

        // AnalyzeRequest already confirmed $validated['type'] is a known
        // registry key — never a class name the client supplied directly.
        $model = $this->registry->modelClass($validated['type'])::findOrFail($validated['id']);

        /** @var array<string,mixed> $overrides */
        $overrides = $validated['fields'] ?? [];

        $build = $this->papers->fromModel($model, $validated['locale'], $overrides);
        $report = $this->runner->analyze($build->paper);

        return response()->json([
            'report' => $report,
            'meta' => [
                'mode' => $overrides === [] ? 'saved' : 'live',
                'content_source' => $build->contentSource,
                'word_count' => $report->insights?->wordCount ?? 0,
                'analyzed_at' => now()->toIso8601String(),
            ],
        ]);
    }
}

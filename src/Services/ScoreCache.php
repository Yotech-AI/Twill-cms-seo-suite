<?php

namespace TwillSeo\Services;

use TwillSeo\Analysis\AnalysisRunner;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessment\ResultCategory;
use TwillSeo\Models\SeoEntry;
use TwillSeo\Services\Settings\SeoSettings;

/**
 * Writes cached scores onto a saved model's SeoEntryTranslation rows, so
 * listing dots and panel numbers never have to run the engine live. Uses the
 * exact same PaperFactory::fromModel() path saved-mode analysis does — same
 * builder, same content resolver — so a cached score and a fresh "saved
 * mode" analyze call always agree.
 *
 * Never the source of a broken save: HandleSeo::afterSaveHandleSeo wraps the
 * call to refresh() in try/catch, not this class itself, so a caller that
 * wants the exception (a console command re-scoring the whole site, say)
 * still can.
 */
final class ScoreCache
{
    public function __construct(
        private readonly ModelRegistry $registry,
        private readonly PaperFactory $papers,
        private readonly AnalysisRunner $runner,
        private readonly SeoSettings $settings,
    ) {}

    public function refresh(object $model): void
    {
        if (! config('twill-seo.analysis.refresh_scores_on_save', true)) {
            return;
        }

        // DB row over config, like every other feature toggle reads through
        // SeoSettings::feature() — a raw config() read here would leave the
        // settings admin's analysis switch unable to stop this from writing.
        if (! $this->settings->feature('analysis')) {
            return;
        }

        if ($this->registry->keyFor($model) === null) {
            return;
        }

        /** @var SeoEntry $entry */
        $entry = $model->seoEntry()->firstOrCreate();

        foreach ((array) config('translatable.locales', ['en']) as $locale) {
            $build = $this->papers->fromModel($model, $locale);
            $report = $this->runner->analyze($build->paper);

            $translation = $entry->translationOrNew($locale);
            $translation->seo_score = $report->seo->score;
            $translation->readability_score = $report->readability->score;
            $translation->analysis_summary = [
                'seo' => $this->tally($report->seo->results),
                'readability' => $this->tally($report->readability->results),
                'insights' => [
                    'words' => $report->insights?->wordCount ?? 0,
                    'reading_time' => $report->insights?->readingTimeMinutes ?? 0,
                    'flesch' => $report->insights?->fleschScore,
                ],
            ];
            $translation->analyzed_at = now();
            $translation->save();
        }
    }

    /**
     * Counts results by category rather than by rating: category is the
     * panel's three-bucket grouping (problems/improvements/good), which is
     * exactly the red/orange/green the listing dots and this summary both
     * use. Feedback and error results count toward neither bucket — they are
     * verdicts about the analysis itself, not the content.
     *
     * @param  list<AssessmentResult>  $results
     * @return array{red: int, orange: int, green: int}
     */
    private function tally(array $results): array
    {
        $tally = ['red' => 0, 'orange' => 0, 'green' => 0];

        foreach ($results as $result) {
            match ($result->category) {
                ResultCategory::Problems => $tally['red']++,
                ResultCategory::Improvements => $tally['orange']++,
                ResultCategory::Good => $tally['green']++,
                default => null,
            };
        }

        return $tally;
    }
}

<?php

namespace TwillSeo\Services;

use TwillSeo\Analysis\Contracts\KeyphraseUsageProvider;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Models\SeoEntry;
use TwillSeo\Models\SeoEntryTranslation;

/**
 * Answers PreviouslyUsedKeyphraseAssessment's question from the package's own
 * storage: is this keyphrase already the focus_keyphrase of some other
 * managed entry, in the same locale.
 */
final class KeyphraseUsage implements KeyphraseUsageProvider
{
    public function countOtherUsages(string $keyphrase, Paper $paper): ?int
    {
        $normalized = mb_strtolower(trim($keyphrase));

        // Not zero: the engine's contract is that "cannot answer" and "the
        // answer is zero" must stay distinguishable, and an empty keyphrase
        // is not a question this store can meaningfully answer.
        if ($normalized === '') {
            return null;
        }

        $query = SeoEntryTranslation::query()
            ->where('locale', $paper->locale)
            ->whereRaw('LOWER(TRIM(focus_keyphrase)) = ?', [$normalized]);

        if (($ownEntryId = $this->ownEntryId($paper)) !== null) {
            $query->where('twill_seo_entry_id', '!=', $ownEntryId);
        }

        return $query->count();
    }

    /**
     * The SeoEntry id belonging to the paper's own model, found via the
     * model_type/model_id PaperFactory stashes in customData — so the
     * paper's own keyphrase never counts as a use "elsewhere". Null (no
     * exclusion) when customData carries neither key, which is the case for
     * any Paper not built through PaperFactory.
     */
    private function ownEntryId(Paper $paper): ?int
    {
        $modelType = $paper->customData['model_type'] ?? null;
        $modelId = $paper->customData['model_id'] ?? null;

        if ($modelType === null || $modelId === null) {
            return null;
        }

        return SeoEntry::query()
            ->where('seoable_type', $modelType)
            ->where('seoable_id', $modelId)
            ->value('id');
    }
}

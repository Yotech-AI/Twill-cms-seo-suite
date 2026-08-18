<?php

namespace TwillSeo\Analysis\Context;

use TwillSeo\Analysis\Assessment\Assessment;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Contracts\KeyphraseUsageProvider;
use TwillSeo\Analysis\Contracts\MessageRenderer;
use TwillSeo\Analysis\Html\ParsedContent;
use TwillSeo\Analysis\Language\LanguagePack;
use TwillSeo\Analysis\Paper\Paper;
use TwillSeo\Analysis\Research\Research;

/**
 * One analysis run. Everything an assessment is allowed to see, plus the
 * per-run memo that stops fifteen assessments from tokenizing the same text
 * fifteen times.
 *
 * Not readonly: the memo fills up as the run proceeds. It is scoped to a
 * single paper and thrown away afterwards, so nothing leaks between runs.
 */
final class AnalysisContext
{
    /** The namespace Laravel resolves this package's language files under. */
    private const MESSAGE_NAMESPACE = 'twill-seo::';

    /** @var array<class-string,mixed> */
    private array $researchResults = [];

    public function __construct(
        public readonly Paper $paper,
        public readonly ParsedContent $content,
        public readonly LanguagePack $language,
        public readonly MessageRenderer $messages,
        public readonly KeyphraseUsageProvider $keyphraseUsage,
    ) {}

    /**
     * @template TResult
     *
     * @param  class-string<Research<TResult>>  $research
     * @return TResult
     */
    public function research(string $research): mixed
    {
        // array_key_exists rather than ??=, so a research that legitimately
        // answers null is not re-run by every assessment that asks.
        if (! array_key_exists($research, $this->researchResults)) {
            $this->researchResults[$research] = (new $research)->run($this);
        }

        return $this->researchResults[$research];
    }

    /**
     * Builds a result for one branch of an assessment, rendering its message.
     * Assessments never build a message key by hand: the key is derived from
     * the identifier so the two can never drift apart.
     *
     * @param  array<string,mixed>  $params
     */
    public function result(Assessment $assessment, int $score, string $branchKey, array $params = []): AssessmentResult
    {
        $key = self::MESSAGE_NAMESPACE.'analysis.'.self::messageGroup($assessment->identifier()).'.'.$branchKey;

        return new AssessmentResult(
            $assessment->identifier(),
            $score,
            $key,
            $params,
            $this->messages->render($key, $params),
        );
    }

    /** metaDescriptionLength => meta_description_length */
    private static function messageGroup(string $identifier): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $identifier));
    }
}

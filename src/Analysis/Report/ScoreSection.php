<?php

namespace TwillSeo\Analysis\Report;

use JsonSerializable;
use TwillSeo\Analysis\Assessment\AssessmentResult;
use TwillSeo\Analysis\Assessor\AssessorResult;
use TwillSeo\Analysis\Score\OverallRating;
use TwillSeo\Analysis\Score\OverallScore;

final readonly class ScoreSection implements JsonSerializable
{
    /**
     * @param  list<AssessmentResult>  $results
     */
    public function __construct(
        public int $score,
        public OverallRating $rating,
        public array $results,
    ) {}

    public static function fromAssessorResult(AssessorResult $result): self
    {
        return new self($result->overall->score, $result->overall->rating, $result->results);
    }

    /**
     * A section that was switched off. It keeps the shape the panel expects
     * rather than disappearing from the report.
     */
    public static function empty(): self
    {
        return self::fromAssessorResult(new AssessorResult([], OverallScore::notAvailable()));
    }

    /**
     * @return array{score:int,rating:string,results:list<array<string,mixed>>}
     */
    public function jsonSerialize(): array
    {
        return [
            'score' => $this->score,
            'rating' => $this->rating->value,
            'results' => array_map(fn (AssessmentResult $result) => $result->jsonSerialize(), $this->results),
        ];
    }
}

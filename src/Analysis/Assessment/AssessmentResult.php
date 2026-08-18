<?php

namespace TwillSeo\Analysis\Assessment;

use JsonSerializable;

/**
 * One assessment's verdict about one paper.
 *
 * Rating, category and score participation are all functions of the score, so
 * they are derived here rather than accepted as constructor arguments: a
 * result carrying a "good" rating next to a score of 3 must not be
 * representable.
 */
final readonly class AssessmentResult implements JsonSerializable
{
    public Rating $rating;

    public ResultCategory $category;

    public bool $countsTowardScore;

    /**
     * @param  array<string,mixed>  $messageParams  the raw numbers behind $text, for a UI that wants to
     *                                              format them itself
     */
    public function __construct(
        public string $identifier,
        public int $score,
        public string $messageKey,
        public array $messageParams,
        public string $text,
    ) {
        $this->rating = Rating::fromScore($score);
        $this->category = ResultCategory::fromRating($this->rating);
        // Feedback (0) and error (-1) describe the analysis itself, not the
        // content, so averaging them in would punish a paper for a check that
        // never reached a verdict. Every penalty score does count.
        $this->countsTowardScore = $score !== 0 && $score !== -1;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->identifier,
            'score' => $this->score,
            'rating' => $this->rating->value,
            'category' => $this->category->value,
            'text' => $this->text,
            'messageKey' => $this->messageKey,
            'params' => $this->messageParams,
        ];
    }
}

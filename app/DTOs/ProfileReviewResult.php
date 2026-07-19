<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class ProfileReviewResult
{
    /**
     * @param  array<int, array{
     *     id: string,
     *     category: string,
     *     field: string,
     *     action: string,
     *     index: int|null,
     *     current: string|null,
     *     suggested: string|null,
     *     rationale: string,
     * }>  $suggestions
     */
    public function __construct(
        public array $suggestions,
        public AiUsage $usage,
        public string $model,
    ) {}
}

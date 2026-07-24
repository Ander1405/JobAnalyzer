<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class CvTailorResult
{
    /**
     * @param  array<int, string>  $experience
     * @param  array<int, string>  $skills
     */
    public function __construct(
        public string $headline,
        public string $summary,
        public array $experience,
        public array $skills,
        public AiUsage $usage,
        public string $model,
    ) {}
}

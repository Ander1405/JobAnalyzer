<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class JobOffer
{
    /**
     * @param  array<int, string>|null  $benefits
     * @param  array<int, string>|null  $requiredSkills
     */
    public function __construct(
        public string $source,
        public string $company,
        public string $title,
        public string $description,
        public string $url,
        public ?string $contractType = null,
        public ?string $salaryRaw = null,
        public ?string $language = null,
        public ?string $applyUrl = null,
        public ?string $location = null,
        public ?bool $isRemote = null,
        public ?string $workMode = null,
        public ?string $seniority = null,
        public ?string $employmentType = null,
        public ?string $postedAt = null,
        public ?string $expiresAt = null,
        public ?string $companyLogo = null,
        public ?string $companyWebsite = null,
        public ?array $benefits = null,
        public ?array $requiredSkills = null,
        public ?int $applicantsCount = null,
    ) {}

    public function hash(): string
    {
        return hash('sha256', $this->source.$this->company.$this->title.$this->url);
    }
}

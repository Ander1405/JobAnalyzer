<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class JobOffer
{
    public function __construct(
        public string $source,
        public string $company,
        public string $title,
        public string $description,
        public string $url,
        public ?string $contractType = null,
        public ?string $salaryRaw = null,
        public ?string $language = null,
    ) {}

    public function hash(): string
    {
        return hash('sha256', $this->source.$this->company.$this->title.$this->url);
    }
}

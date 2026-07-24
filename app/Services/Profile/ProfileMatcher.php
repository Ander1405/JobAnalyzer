<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\Job;
use App\Models\Profile;
use App\Support\ProfileFile;

class ProfileMatcher
{
    /** @var array<int, string> */
    private array $primarySkills = [];

    public function __construct(private readonly string $perfilMd)
    {
        $this->primarySkills = $this->extractPrimarySkills();
    }

    public static function create(): self
    {
        $profile = Profile::active();
        $perfilMd = $profile !== null
            ? $profile->raw_md
            : (string) file_get_contents(ProfileFile::path());

        return new self($perfilMd);
    }

    public function isRelevant(Job $job): bool
    {
        if (empty($this->primarySkills)) {
            return true;
        }

        $jobText = strtolower($job->title.' '.$job->description);

        foreach ($this->primarySkills as $skill) {
            if (str_contains($jobText, $skill)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function extractPrimarySkills(): array
    {
        $profileLower = strtolower($this->perfilMd);

        $primarySkills = ['php', 'laravel', 'vue', 'javascript'];

        return array_values(array_filter(
            $primarySkills,
            fn (string $skill) => str_contains($profileLower, $skill),
        ));
    }
}

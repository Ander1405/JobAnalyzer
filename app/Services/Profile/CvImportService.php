<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\Profile;

class CvImportService
{
    public function __construct(
        private readonly CvParser $parser,
        private readonly ProfileBuilder $builder,
    ) {}

    /**
     * Parses a CV file (deterministically, no AI) and stores it as the given profile
     * slug — creating or overwriting it — writing `storage/app/perfil.md` when it is
     * the active profile.
     */
    public function import(string $absolutePath, string $slug = 'default', ?string $extension = null): Profile
    {
        $parsed = $this->parser->parse($absolutePath, $extension);
        $rawMd = $this->builder->toMarkdown($parsed);

        Profile::where('slug', '!=', $slug)->where('is_active', true)->update(['is_active' => false]);

        $profile = Profile::updateOrCreate(
            ['slug' => $slug],
            [
                'label' => $parsed['headline'] ?? $parsed['contact']['name'] ?? ucfirst($slug),
                'contact' => $parsed['contact'],
                'headline' => $parsed['headline'],
                'summary' => $parsed['summary'],
                'experience' => $parsed['experience'],
                'skills' => $parsed['skills'],
                'education' => $parsed['education'],
                'languages' => $parsed['languages'],
                'certifications' => $parsed['certifications'],
                'raw_md' => $rawMd,
                'source_text' => $parsed['source_text'],
                'is_active' => true,
            ],
        );

        file_put_contents(storage_path('app/perfil.md'), $rawMd);

        return $profile;
    }
}

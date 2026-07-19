<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\Profile;
use RuntimeException;

class ProfileVariantService
{
    public function __construct(private readonly ProfileBuilder $builder) {}

    /**
     * Creates a new profile variant from the real content of `default`. Only fields
     * present in $overrides are changed — everything else is copied verbatim, so a
     * variant can never contain content that isn't already in the real CV.
     *
     * @param  array<string, mixed>  $overrides
     */
    public function createVariant(string $slug, string $label, array $overrides = []): Profile
    {
        $default = Profile::where('slug', 'default')->first();

        if ($default === null) {
            throw new RuntimeException('Import a CV into the "default" profile before creating variants.');
        }

        $data = [
            'contact' => $default->contact,
            'headline' => $overrides['headline'] ?? $default->headline,
            'summary' => $overrides['summary'] ?? $default->summary,
            'experience' => $overrides['experience'] ?? $default->experience,
            'skills' => $overrides['skills'] ?? $default->skills,
            'education' => $overrides['education'] ?? $default->education,
            'languages' => $default->languages,
            'certifications' => $overrides['certifications'] ?? $default->certifications,
        ];

        return Profile::create([
            'slug' => $slug,
            'label' => $label,
            ...$data,
            'raw_md' => $this->builder->toMarkdown($data),
            'source_text' => $default->source_text,
            'is_active' => false,
        ]);
    }

    /**
     * Activates a profile variant: only one profile is active at a time, and its
     * Markdown becomes `storage/app/perfil.md`, the file `jobs:analyze` reads from.
     */
    public function activate(Profile $profile): Profile
    {
        Profile::where('id', '!=', $profile->id)->where('is_active', true)->update(['is_active' => false]);
        $profile->update(['is_active' => true]);

        file_put_contents(storage_path('app/perfil.md'), $profile->raw_md);

        return $profile->fresh();
    }

    /**
     * Re-parses `storage/app/perfil.md` back into structure for the currently active
     * profile, by its `##` headers. Deterministic, no AI involved. When $content is
     * given (the Vue raw-Markdown editor), it is written to perfil.md first — so
     * editing either the file by hand or the textarea in the browser both work.
     */
    public function syncActiveFromFile(?string $content = null): Profile
    {
        $active = Profile::active();

        if ($active === null) {
            throw new RuntimeException('No active profile to sync. Import a CV first.');
        }

        if ($content !== null) {
            file_put_contents(storage_path('app/perfil.md'), $content);
        }

        $markdown = (string) file_get_contents(storage_path('app/perfil.md'));
        $structured = $this->builder->fromMarkdown($markdown);

        $active->update([
            'headline' => $structured['headline'],
            'summary' => $structured['summary'],
            'experience' => $structured['experience'],
            'skills' => $structured['skills'],
            'education' => $structured['education'],
            'languages' => $structured['languages'],
            'certifications' => $structured['certifications'],
            'contact' => $structured['contact'],
            'raw_md' => $markdown,
        ]);

        return $active->fresh();
    }

    /**
     * Regenerates `raw_md` from a profile's structured fields — used after editing the
     * profile through the Vue form — and refreshes `perfil.md` if it is the active one.
     */
    public function regenerateMarkdown(Profile $profile): Profile
    {
        $rawMd = $this->builder->toMarkdown([
            'contact' => $profile->contact,
            'headline' => $profile->headline,
            'summary' => $profile->summary,
            'experience' => $profile->experience ?? [],
            'skills' => $profile->skills ?? [],
            'education' => $profile->education ?? [],
            'languages' => $profile->languages ?? ['items' => [], 'english_level' => null],
            'certifications' => $profile->certifications ?? [],
        ]);

        $profile->update(['raw_md' => $rawMd]);

        if ($profile->is_active) {
            file_put_contents(storage_path('app/perfil.md'), $rawMd);
        }

        return $profile->fresh();
    }
}

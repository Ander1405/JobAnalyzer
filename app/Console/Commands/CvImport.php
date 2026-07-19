<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Profile\CvImportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('cv:import {path : Absolute or relative path to the CV file (pdf, txt or md)} {--slug=default : Profile slug to create or overwrite}')]
#[Description('Import a CV file, parse it deterministically (no AI), and store it as a profile.')]
class CvImport extends Command
{
    public function handle(CvImportService $service): int
    {
        $path = (string) $this->argument('path');
        $slug = (string) $this->option('slug');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        try {
            $profile = $service->import(realpath($path) ?: $path, $slug);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $skillsCount = count($profile->skills ?? []);

        $this->info("Profile [{$profile->slug}] imported from {$path}.");
        $this->line('Skills detected: '.($skillsCount > 0 ? (string) $skillsCount : 'none'));
        $this->line('English level: '.($profile->languages['english_level'] ?? 'not detected'));

        return self::SUCCESS;
    }
}

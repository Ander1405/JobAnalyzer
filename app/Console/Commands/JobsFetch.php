<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Job;
use App\Services\Sources\InfoJobsFetcher;
use App\Services\Sources\JobSourceInterface;
use App\Services\Sources\JSearchFetcher;
use App\Services\Sources\LaraJobsRssFetcher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('jobs:fetch')]
#[Description('Fetch job offers from all configured sources and store new ones locally.')]
class JobsFetch extends Command
{
    /**
     * @var array<class-string<JobSourceInterface>>
     */
    private const SOURCES = [
        JSearchFetcher::class,
        LaraJobsRssFetcher::class,
        InfoJobsFetcher::class,
    ];

    public function handle(): int
    {
        $created = 0;
        $duplicates = 0;
        $errors = 0;

        foreach (self::SOURCES as $sourceClass) {
            /** @var JobSourceInterface $source */
            $source = app($sourceClass);

            try {
                foreach ($source->fetch() as $offer) {
                    $job = Job::firstOrCreate(
                        ['hash' => $offer->hash()],
                        [
                            'source' => $offer->source,
                            'company' => $offer->company,
                            'title' => $offer->title,
                            'description' => $offer->description,
                            'url' => $offer->url,
                            'contract_type' => $offer->contractType,
                            'salary_raw' => $offer->salaryRaw,
                            'language' => $offer->language,
                        ],
                    );

                    $job->wasRecentlyCreated ? $created++ : $duplicates++;
                }
            } catch (Throwable $exception) {
                $errors++;
                Log::error("Job source [{$sourceClass}] failed to fetch.", [
                    'exception' => $exception->getMessage(),
                ]);
                $this->error("Source {$sourceClass} failed: {$exception->getMessage()}");
            }
        }

        $this->info("{$created} new, {$duplicates} duplicates, {$errors} errors across sources.");

        return self::SUCCESS;
    }
}

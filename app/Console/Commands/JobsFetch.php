<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RequiresUserContext;
use App\Enums\JobStatus;
use App\Jobs\AnalyzeJobListing;
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

#[Signature('jobs:fetch {--queue : Dispatch the analysis of new offers to the queue instead of running it inline} {--user= : Email of the user to fetch listings for}')]
#[Description('Fetch job offers from all configured sources and store new ones locally.')]
class JobsFetch extends Command
{
    use RequiresUserContext;

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
        $user = $this->resolveUserOption();

        if ($user === null) {
            return self::FAILURE;
        }

        $created = 0;
        $duplicates = 0;
        $errors = 0;
        /** @var array<int, Job> $newJobs */
        $newJobs = [];

        foreach (self::SOURCES as $sourceClass) {
            /** @var JobSourceInterface $source */
            $source = app($sourceClass);

            try {
                foreach ($source->fetch() as $offer) {
                    $job = Job::firstOrCreate(
                        ['hash' => $offer->hash(), 'user_id' => $user->id],
                        [
                            'source' => $offer->source,
                            'company' => $offer->company,
                            'title' => $offer->title,
                            'description' => $offer->description,
                            'url' => $offer->url,
                            'contract_type' => $offer->contractType,
                            'salary_raw' => $offer->salaryRaw,
                            'language' => $offer->language,
                            'apply_url' => $offer->applyUrl ?? $offer->url,
                            'location' => $offer->location,
                            'is_remote' => $offer->isRemote,
                            'work_mode' => $offer->workMode,
                            'seniority' => $offer->seniority,
                            'employment_type' => $offer->employmentType,
                            'posted_at' => $offer->postedAt,
                            'expires_at' => $offer->expiresAt,
                            'company_logo' => $offer->companyLogo,
                            'company_website' => $offer->companyWebsite,
                            'benefits' => $offer->benefits,
                            'required_skills' => $offer->requiredSkills,
                            'applicants_count' => $offer->applicantsCount,
                        ],
                    );

                    if ($job->wasRecentlyCreated) {
                        $created++;
                        $newJobs[] = $job;
                    } else {
                        $duplicates++;
                    }
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

        if ($created === 0) {
            return self::SUCCESS;
        }

        if ($this->option('queue')) {
            $this->dispatchAnalysis($newJobs);

            return self::SUCCESS;
        }

        $this->info('Starting analysis of new jobs...');
        $this->call('jobs:analyze', ['--user' => $user->email]);

        return self::SUCCESS;
    }

    /**
     * Marking them "analyzing" up front keeps `jobs:analyze` (which only picks up
     * "fetched" offers) from re-analyzing what the worker is already holding.
     *
     * @param  array<int, Job>  $newJobs
     */
    private function dispatchAnalysis(array $newJobs): void
    {
        foreach ($newJobs as $job) {
            $job->update(['status' => JobStatus::Analyzing, 'error_message' => null]);

            AnalyzeJobListing::dispatch($job);
        }

        $this->info(count($newJobs).' job(s) queued for analysis.');
    }
}

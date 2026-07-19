<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\JobStatus;
use App\Models\Job;
use App\Services\AI\JobAnalyzer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('jobs:analyze')]
#[Description('Analyze fetched job offers against the local profile using the configured AI provider.')]
class JobsAnalyze extends Command
{
    public function handle(JobAnalyzer $analyzer): int
    {
        $jobs = Job::where('status', JobStatus::Fetched)->get();

        $analyzed = 0;
        $failed = 0;

        foreach ($jobs as $job) {
            $analyzer->analyze($job);

            $job->fresh()->status === JobStatus::Analyzed ? $analyzed++ : $failed++;

            sleep(1);
        }

        $this->info("{$analyzed} analyzed, {$failed} failed out of {$jobs->count()} pending jobs.");

        return self::SUCCESS;
    }
}

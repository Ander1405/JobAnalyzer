<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RequiresUserContext;
use App\Enums\JobStatus;
use App\Models\Job;
use App\Services\Jobs\JobAnalysisPipeline;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('jobs:analyze {--user= : Email of the user whose fetched offers to analyze}')]
#[Description('Analyze fetched job offers against the local profile using the configured AI provider.')]
class JobsAnalyze extends Command
{
    use RequiresUserContext;

    public function handle(JobAnalysisPipeline $pipeline): int
    {
        $user = $this->resolveUserOption();

        if ($user === null) {
            return self::FAILURE;
        }

        $jobs = Job::where('status', JobStatus::Fetched)->where('user_id', $user->id)->get();

        $analyzed = 0;
        $failed = 0;
        $discarded = 0;
        $lowMatch = 0;
        $minMatch = (int) config('jobhunter.min_match_to_publish', 75);

        foreach ($jobs as $job) {
            $pipeline->process($job);

            $job->refresh();

            match (true) {
                $job->status === JobStatus::Discarded => $discarded++,
                $job->status !== JobStatus::Analyzed => $failed++,
                (int) ($job->ai_analysis['match_score'] ?? 0) < $minMatch => $lowMatch++,
                default => $analyzed++,
            };

            sleep(1);
        }

        $this->info("{$analyzed} above {$minMatch}%, {$lowMatch} below (kept, hidden by the marketplace filter), {$failed} failed, {$discarded} discarded by the profile pre-filter, out of {$jobs->count()} jobs.");

        if ($failed > 0) {
            $this->warn('Some analyses failed. Check storage/logs/laravel.log for the provider error.');
        }

        return self::SUCCESS;
    }
}

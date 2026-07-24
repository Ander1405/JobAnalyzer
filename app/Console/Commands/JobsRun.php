<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RequiresUserContext;
use App\Enums\JobStatus;
use App\Models\Job;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

#[Signature('jobs:run {--user= : Email of the user to run the full pipeline for}')]
#[Description('Run the full pipeline: fetch, analyze, and publish job offers.')]
class JobsRun extends Command
{
    use RequiresUserContext;

    public function handle(): int
    {
        $user = $this->resolveUserOption();

        if ($user === null) {
            return self::FAILURE;
        }

        $this->call('jobs:fetch', ['--user' => $user->email]);

        $pendingIds = Job::where('status', JobStatus::Fetched)->where('user_id', $user->id)->pluck('id');

        if ($pendingIds->isNotEmpty()) {
            $this->call('jobs:analyze', ['--user' => $user->email]);
        }

        $this->call('jobs:publish', ['--user' => $user->email]);

        $this->summarize($pendingIds);

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, int>  $pendingIds
     */
    private function summarize($pendingIds): void
    {
        $threshold = (int) config('jobhunter.match_score_alert_threshold');

        $highlights = Job::whereIn('id', $pendingIds)
            ->get()
            ->filter(fn (Job $job) => ($job->ai_analysis['match_score'] ?? -1) >= $threshold);

        $this->newLine();
        $this->info('=== jobs:run summary ===');

        if ($highlights->isEmpty()) {
            $this->line("No new jobs reached the {$threshold}% match score alert threshold.");

            return;
        }

        $this->warn("{$highlights->count()} new job(s) reached {$threshold}%+ match:");

        foreach ($highlights as $job) {
            $this->line("- [{$job->ai_analysis['match_score']}%] {$job->title} @ {$job->company} ({$job->url})");
        }
    }
}

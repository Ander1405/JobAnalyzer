<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\JobStatus;
use App\Models\Job;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

#[Signature('jobs:run')]
#[Description('Run the full pipeline: fetch, analyze, and publish job offers.')]
class JobsRun extends Command
{
    public function handle(): int
    {
        $this->call('jobs:fetch');

        $pendingIds = Job::where('status', JobStatus::Fetched)->pluck('id');

        $this->call('jobs:analyze');
        $this->call('jobs:publish');

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

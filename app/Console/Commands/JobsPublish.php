<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RequiresUserContext;
use App\Enums\JobStatus;
use App\Models\Job;
use App\Services\Notion\NotionPublisher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('jobs:publish {--user= : Email of the user whose analyzed offers to publish}')]
#[Description('Publish analyzed job offers at or above MIN_MATCH_TO_PUBLISH to Notion as a searchable backup.')]
class JobsPublish extends Command
{
    use RequiresUserContext;

    public function handle(NotionPublisher $publisher): int
    {
        $user = $this->resolveUserOption();

        if ($user === null) {
            return self::FAILURE;
        }

        $jobs = Job::where('status', JobStatus::Analyzed)->where('user_id', $user->id)->get();

        $published = 0;
        $failed = 0;
        $belowThreshold = 0;

        foreach ($jobs as $job) {
            if (! $publisher->isEligible($job)) {
                $belowThreshold++;

                continue;
            }

            $publisher->publish($job);

            $job->fresh()->status === JobStatus::Published ? $published++ : $failed++;

            usleep(350000);
        }

        $this->info("{$published} published, {$failed} failed, {$belowThreshold} below match threshold out of {$jobs->count()} analyzed jobs.");

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\JobStatus;
use App\Models\Job;
use App\Services\Notion\NotionPublisher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('jobs:publish')]
#[Description('Publish analyzed job offers to Notion as a searchable backup.')]
class JobsPublish extends Command
{
    public function handle(NotionPublisher $publisher): int
    {
        $jobs = Job::where('status', JobStatus::Analyzed)->get();

        $published = 0;
        $failed = 0;

        foreach ($jobs as $job) {
            $publisher->publish($job);

            $job->fresh()->status === JobStatus::Published ? $published++ : $failed++;

            usleep(350000);
        }

        $this->info("{$published} published, {$failed} failed out of {$jobs->count()} analyzed jobs.");

        return self::SUCCESS;
    }
}

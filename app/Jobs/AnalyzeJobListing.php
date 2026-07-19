<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Job as JobListing;
use App\Services\AI\JobAnalyzer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AnalyzeJobListing implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly JobListing $listing) {}

    public function handle(JobAnalyzer $analyzer): void
    {
        $analyzer->analyze($this->listing);
    }
}

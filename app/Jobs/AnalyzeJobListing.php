<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\JobStatus;
use App\Models\Job as JobListing;
use App\Services\Jobs\JobAnalysisPipeline;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AnalyzeJobListing implements ShouldQueue
{
    use Queueable;

    /** An AI call can legitimately take a couple of minutes; the queue default of 60s cannot. */
    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(
        public readonly JobListing $listing,
        public readonly bool $skipPreFilter = false,
    ) {
        /**
         * Its own queue, separate from the default one FetchJobListings uses: a single
         * long fetch run would otherwise sit in front of every already-fetched job's
         * analysis, and multiple analysis workers (see AppServiceProvider) only run
         * jobs concurrently if they're not sharing a queue with something else.
         */
        $this->onQueue('analysis');
    }

    public function handle(JobAnalysisPipeline $pipeline): void
    {
        // Queue workers run in their own process with no authenticated user; the
        // BelongsToUser scope (Profile::active(), etc.) needs one bound explicitly
        // so this listing's owner — not whoever's session happens to be resolved —
        // is who the analysis runs against.
        Auth::onceUsingId($this->listing->user_id);

        $pipeline->process($this->listing, $this->skipPreFilter);
    }

    /**
     * Without this the listing stays at "analyzing" forever whenever the worker
     * kills the job (timeout, fatal error), and the UI polls it indefinitely.
     */
    public function failed(?Throwable $exception): void
    {
        Auth::onceUsingId($this->listing->user_id);

        $this->listing->update([
            'status' => JobStatus::Failed,
            'error_message' => $exception?->getMessage() ?? 'El análisis no pudo completarse.',
        ]);
    }
}

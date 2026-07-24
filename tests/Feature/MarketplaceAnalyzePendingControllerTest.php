<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Jobs\AnalyzeJobListing;
use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class MarketplaceAnalyzePendingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_analysis_for_every_fetched_job_and_marks_them_analyzing(): void
    {
        Bus::fake();

        $pending = Job::factory()->count(2)->create(['status' => JobStatus::Fetched]);
        $alreadyAnalyzed = Job::factory()->analyzed()->create();

        $response = $this->postJson('/api/marketplace/analyze-pending');

        $response->assertOk();
        $response->assertJsonCount(2, 'dispatched');

        foreach ($pending as $job) {
            $this->assertSame(JobStatus::Analyzing, $job->fresh()->status);
            Bus::assertDispatched(AnalyzeJobListing::class, fn (AnalyzeJobListing $queued) => $queued->listing->is($job));
        }

        Bus::assertNotDispatched(AnalyzeJobListing::class, fn (AnalyzeJobListing $queued) => $queued->listing->is($alreadyAnalyzed));
        $this->assertSame(JobStatus::Analyzed, $alreadyAnalyzed->fresh()->status);
    }

    public function test_it_does_nothing_when_there_is_nothing_pending(): void
    {
        Bus::fake();

        $response = $this->postJson('/api/marketplace/analyze-pending');

        $response->assertOk();
        $response->assertJsonCount(0, 'dispatched');
        Bus::assertNotDispatched(AnalyzeJobListing::class);
    }
}

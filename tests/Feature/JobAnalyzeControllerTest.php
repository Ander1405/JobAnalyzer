<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Jobs\AnalyzeJobListing;
use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class JobAnalyzeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_queues_the_analysis_and_marks_the_job_as_analyzing_immediately(): void
    {
        Bus::fake();

        $job = Job::factory()->create(['status' => JobStatus::Fetched]);

        $response = $this->postJson("/api/jobs/{$job->id}/analyze");

        $response->assertOk()->assertJsonPath('status', JobStatus::Analyzing->value);

        $this->assertSame(JobStatus::Analyzing, $job->fresh()->status);

        Bus::assertDispatched(AnalyzeJobListing::class, fn (AnalyzeJobListing $queued) => $queued->listing->is($job));
    }

    public function test_it_clears_a_previous_error_message_when_re_queued(): void
    {
        Bus::fake();

        $job = Job::factory()->create([
            'status' => JobStatus::Failed,
            'error_message' => 'Claude CLI exited with code 127.',
        ]);

        $this->postJson("/api/jobs/{$job->id}/analyze")->assertOk();

        $this->assertNull($job->fresh()->error_message);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Job;
use App\Models\TrackedJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobShowControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_includes_the_tracked_job_when_present(): void
    {
        $job = Job::factory()->create();
        $trackedJob = TrackedJob::factory()->create(['job_id' => $job->id]);

        $response = $this->getJson("/api/jobs/{$job->id}");

        $response->assertOk();
        $response->assertJsonPath('tracked_job.id', $trackedJob->id);
    }

    public function test_tracked_job_is_null_when_not_tracked(): void
    {
        $job = Job::factory()->create();

        $response = $this->getJson("/api/jobs/{$job->id}");

        $response->assertOk();
        $response->assertJsonPath('tracked_job', null);
    }
}

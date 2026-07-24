<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Job;
use App\Models\TrackedJob;
use App\Models\TrackedJobComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_tracked_jobs_with_their_job(): void
    {
        $job = Job::factory()->create();
        TrackedJob::factory()->create(['job_id' => $job->id]);

        $response = $this->getJson('/api/tracking');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.job.id', $job->id);
    }

    public function test_index_includes_the_latest_comment(): void
    {
        $trackedJob = TrackedJob::factory()->create();
        TrackedJobComment::factory()->create([
            'tracked_job_id' => $trackedJob->id,
            'body' => 'Primer comentario',
            'created_at' => now()->subDay(),
        ]);
        TrackedJobComment::factory()->create([
            'tracked_job_id' => $trackedJob->id,
            'body' => 'Comentario más reciente',
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/tracking');

        $response->assertOk();
        $response->assertJsonPath('0.latest_comment.body', 'Comentario más reciente');
    }

    public function test_show_returns_the_tracked_job_with_job_and_comments(): void
    {
        $trackedJob = TrackedJob::factory()->create();
        TrackedJobComment::factory()->create(['tracked_job_id' => $trackedJob->id]);

        $response = $this->getJson("/api/tracking/{$trackedJob->id}");

        $response->assertOk();
        $response->assertJsonPath('id', $trackedJob->id);
        $response->assertJsonPath('job.id', $trackedJob->job_id);
        $response->assertJsonCount(1, 'comments');
    }
}

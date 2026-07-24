<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\TrackedJob;
use App\Models\TrackedJobComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingDestroyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_the_tracked_job_and_its_comments(): void
    {
        $trackedJob = TrackedJob::factory()->create();
        $comment = TrackedJobComment::factory()->create(['tracked_job_id' => $trackedJob->id]);

        $response = $this->deleteJson("/api/tracking/{$trackedJob->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('tracked_jobs', ['id' => $trackedJob->id]);
        $this->assertDatabaseMissing('tracked_job_comments', ['id' => $comment->id]);
    }
}

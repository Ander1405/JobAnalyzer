<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TrackedJobStatus;
use App\Models\Job;
use App\Models\TrackedJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceTrackBulkControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_tracked_jobs_for_every_given_id(): void
    {
        $jobs = Job::factory()->count(3)->create();

        $response = $this->postJson('/api/marketplace/track-bulk', [
            'job_ids' => $jobs->pluck('id')->all(),
        ]);

        $response->assertOk();
        $response->assertJsonCount(3, 'tracked');

        foreach ($jobs as $job) {
            $this->assertDatabaseHas('tracked_jobs', [
                'job_id' => $job->id,
                'status' => TrackedJobStatus::SinAplicar->value,
            ]);
        }
    }

    public function test_it_does_not_duplicate_already_tracked_jobs(): void
    {
        $job = Job::factory()->create();
        $existing = TrackedJob::factory()->create(['job_id' => $job->id]);

        $response = $this->postJson('/api/marketplace/track-bulk', [
            'job_ids' => [$job->id],
        ]);

        $response->assertOk();
        $response->assertJsonPath('tracked.0.id', $existing->id);
        $this->assertSame(1, TrackedJob::query()->where('job_id', $job->id)->count());
    }

    public function test_it_rejects_an_empty_list(): void
    {
        $response = $this->postJson('/api/marketplace/track-bulk', ['job_ids' => []]);

        $response->assertUnprocessable();
    }

    public function test_it_rejects_ids_that_do_not_exist(): void
    {
        $response = $this->postJson('/api/marketplace/track-bulk', ['job_ids' => [999999]]);

        $response->assertUnprocessable();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TrackedJobStatus;
use App\Models\Job;
use App\Models\TrackedJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceTrackControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_tracked_job_in_sin_aplicar(): void
    {
        $job = Job::factory()->create();

        $response = $this->postJson("/api/marketplace/{$job->id}/track");

        $response->assertOk();
        $response->assertJsonPath('job_id', $job->id);
        $response->assertJsonPath('status', TrackedJobStatus::SinAplicar->value);
        $this->assertDatabaseHas('tracked_jobs', ['job_id' => $job->id]);
    }

    public function test_it_is_idempotent_when_the_job_is_already_tracked(): void
    {
        $job = Job::factory()->create();
        $existing = TrackedJob::factory()->create(['job_id' => $job->id]);

        $response = $this->postJson("/api/marketplace/{$job->id}/track");

        $response->assertOk();
        $response->assertJsonPath('id', $existing->id);
        $this->assertSame(1, TrackedJob::query()->where('job_id', $job->id)->count());
    }
}

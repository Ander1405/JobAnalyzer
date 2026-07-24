<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Enums\TrackedJobStatus;
use App\Models\Job;
use App\Models\TrackedJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavBadgesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_counts_analyzed_jobs_without_a_tracked_job_as_marketplace_badge(): void
    {
        Job::factory()->count(2)->analyzed()->create();
        Job::factory()->create(['status' => JobStatus::Fetched]);

        $tracked = Job::factory()->analyzed()->create();
        TrackedJob::factory()->create(['job_id' => $tracked->id]);

        $response = $this->getJson('/api/nav/badges');

        $response->assertOk();
        $response->assertJsonPath('marketplace', 2);
    }

    public function test_it_excludes_analyzed_jobs_below_the_publish_threshold_from_marketplace_badge(): void
    {
        // Matches the same min_match_to_publish threshold the Marketplace list
        // applies by default, so the badge never promises more than the list shows.
        Job::factory()->analyzed()->create();
        Job::factory()->analyzed()->create(['ai_analysis' => ['match_score' => 10]]);

        $response = $this->getJson('/api/nav/badges');

        $response->assertOk();
        $response->assertJsonPath('marketplace', 1);
    }

    public function test_it_counts_tracked_jobs_en_proceso_as_tracking_badge(): void
    {
        TrackedJob::factory()->count(2)->create(['status' => TrackedJobStatus::EnProceso]);
        TrackedJob::factory()->create(['status' => TrackedJobStatus::SinAplicar]);

        $response = $this->getJson('/api/nav/badges');

        $response->assertOk();
        $response->assertJsonPath('tracking', 2);
    }
}

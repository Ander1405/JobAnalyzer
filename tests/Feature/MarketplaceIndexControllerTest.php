<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Job;
use App\Models\TrackedJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceIndexControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_by_source(): void
    {
        Job::factory()->create(['source' => 'jsearch']);
        Job::factory()->create(['source' => 'larajobs']);

        $response = $this->getJson('/api/marketplace?source=jsearch');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.source', 'jsearch');
    }

    public function test_it_filters_by_work_mode_and_seniority(): void
    {
        Job::factory()->create(['work_mode' => 'Remoto', 'seniority' => 'Senior']);
        Job::factory()->create(['work_mode' => 'Presencial', 'seniority' => 'Junior']);

        $response = $this->getJson('/api/marketplace?work_mode=Remoto&seniority=Senior');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_it_filters_by_language_inside_the_ai_analysis(): void
    {
        Job::factory()->analyzed()->create(); // idioma: Español

        $english = Job::factory()->analyzed()->create();
        $english->update(['ai_analysis' => array_merge($english->ai_analysis, ['idioma' => 'Inglés'])]);

        $response = $this->getJson('/api/marketplace?language=Inglés');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $english->id);
    }

    public function test_min_match_hides_both_low_scoring_and_unanalyzed_jobs(): void
    {
        Job::factory()->create(); // no ai_analysis

        $lowMatch = Job::factory()->analyzed()->create();
        $lowMatch->update(['ai_analysis' => array_merge($lowMatch->ai_analysis, ['match_score' => 10])]);

        $highMatch = Job::factory()->analyzed()->create();
        $highMatch->update(['ai_analysis' => array_merge($highMatch->ai_analysis, ['match_score' => 95])]);

        $response = $this->getJson('/api/marketplace?min_match=80');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $highMatch->id);
        // Still discoverable, just not as a result: the counter drives the
        // "Analizar pendientes" button.
        $response->assertJsonPath('meta.pending_analysis', 1);
    }

    public function test_it_can_hide_already_tracked_jobs(): void
    {
        $tracked = Job::factory()->create();
        TrackedJob::factory()->create(['job_id' => $tracked->id]);
        Job::factory()->create();

        $response = $this->getJson('/api/marketplace?hide_tracked=1');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_it_can_filter_jobs_with_a_salary_only(): void
    {
        Job::factory()->create(['salary_raw' => '4.000 USD']);
        Job::factory()->create(['salary_raw' => null]);

        $response = $this->getJson('/api/marketplace?has_salary_only=1');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_it_sorts_by_match_score_descending_by_default(): void
    {
        $low = Job::factory()->analyzed()->create();
        $low->update(['ai_analysis' => array_merge($low->ai_analysis, ['match_score' => 40])]);

        $high = Job::factory()->analyzed()->create();
        $high->update(['ai_analysis' => array_merge($high->ai_analysis, ['match_score' => 95])]);

        $response = $this->getJson('/api/marketplace');

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $high->id);
        $response->assertJsonPath('data.1.id', $low->id);
    }

    public function test_it_includes_the_tracked_job_when_present(): void
    {
        $job = Job::factory()->create();
        $trackedJob = TrackedJob::factory()->create(['job_id' => $job->id]);

        $response = $this->getJson('/api/marketplace');

        $response->assertOk();
        $response->assertJsonPath('data.0.tracked_job.id', $trackedJob->id);
    }

    public function test_it_sorts_by_most_recent(): void
    {
        $older = Job::factory()->create(['created_at' => now()->subDays(2)]);
        $newer = Job::factory()->create(['created_at' => now()]);

        $response = $this->getJson('/api/marketplace?sort=recent');

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $newer->id);
        $response->assertJsonPath('data.1.id', $older->id);
    }
}

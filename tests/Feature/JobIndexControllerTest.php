<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobIndexControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_paginates_with_a_default_page_size_of_20(): void
    {
        Job::factory()->count(25)->create();

        $response = $this->getJson('/api/jobs');

        $response->assertOk();
        $response->assertJsonCount(20, 'data');
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.per_page', 20);
        $response->assertJsonPath('meta.total', 25);
        $response->assertJsonPath('meta.last_page', 2);
    }

    public function test_it_respects_a_custom_per_page_and_page(): void
    {
        Job::factory()->count(25)->create();

        $response = $this->getJson('/api/jobs?per_page=10&page=2');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
        $response->assertJsonPath('meta.current_page', 2);
        $response->assertJsonPath('meta.last_page', 3);
    }

    public function test_it_clamps_a_page_number_beyond_the_last_page(): void
    {
        Job::factory()->count(5)->create();

        $response = $this->getJson('/api/jobs?per_page=20&page=99');

        $response->assertOk();
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonCount(5, 'data');
    }

    public function test_it_still_sorts_by_match_score_descending_within_the_page(): void
    {
        Job::factory()->create(['ai_analysis' => ['match_score' => 40]]);
        Job::factory()->create(['ai_analysis' => ['match_score' => 90]]);
        Job::factory()->create(['ai_analysis' => ['match_score' => 60]]);

        $response = $this->getJson('/api/jobs');

        $scores = collect($response->json('data'))->pluck('ai_analysis.match_score');
        $this->assertSame([90, 60, 40], $scores->all());
    }

    public function test_min_match_filter_never_hides_jobs_that_have_not_been_analyzed_yet(): void
    {
        Job::factory()->create(['ai_analysis' => null]);
        Job::factory()->create(['ai_analysis' => ['match_score' => 40]]);
        Job::factory()->create(['ai_analysis' => ['match_score' => 90]]);

        $response = $this->getJson('/api/jobs?min_match=75');

        $response->assertJsonCount(2, 'data');
        $scores = collect($response->json('data'))->pluck('ai_analysis.match_score');
        $this->assertSame([90, null], $scores->all());
    }
}

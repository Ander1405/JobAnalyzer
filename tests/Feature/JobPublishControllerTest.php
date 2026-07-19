<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class JobPublishControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_publishes_a_job_at_or_above_the_threshold(): void
    {
        config([
            'jobhunter.notion.token' => 'test-token',
            'jobhunter.notion.database_id' => 'test-database-id',
            'jobhunter.min_match_to_publish' => 75,
        ]);

        Http::fake(['api.notion.com/*' => Http::response(['id' => 'notion-page-123'], 200)]);

        $job = Job::factory()->analyzed()->create(['ai_analysis' => ['match_score' => 80]]);

        $response = $this->postJson("/api/jobs/{$job->id}/publish");

        $response->assertOk()->assertJsonPath('status', 'published');
    }

    public function test_it_rejects_publishing_a_job_below_the_threshold(): void
    {
        config(['jobhunter.min_match_to_publish' => 75]);
        Http::fake();

        $job = Job::factory()->analyzed()->create(['ai_analysis' => ['match_score' => 60]]);

        $response = $this->postJson("/api/jobs/{$job->id}/publish");

        $response->assertStatus(422);
        $this->assertSame('analyzed', $job->fresh()->status->value);
        Http::assertNothingSent();
    }
}

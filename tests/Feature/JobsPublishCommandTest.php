<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class JobsPublishCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_only_publishes_jobs_at_or_above_the_match_threshold(): void
    {
        config([
            'jobhunter.notion.token' => 'test-token',
            'jobhunter.notion.database_id' => 'test-database-id',
            'jobhunter.min_match_to_publish' => 75,
        ]);

        Http::fake(['api.notion.com/*' => Http::response(['id' => 'notion-page-123'], 200)]);

        $eligible = Job::factory()->analyzed()->create(['ai_analysis' => ['match_score' => 80]]);
        $ineligible = Job::factory()->analyzed()->create(['ai_analysis' => ['match_score' => 60]]);

        $this->artisan('jobs:publish')->assertSuccessful();

        $this->assertSame(JobStatus::Published, $eligible->fresh()->status);
        $this->assertSame(JobStatus::Analyzed, $ineligible->fresh()->status);
        $this->assertNull($ineligible->fresh()->notion_page_id);

        Http::assertSentCount(1);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobSourcesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_the_distinct_sources_actually_present_in_the_database(): void
    {
        Job::factory()->create(['source' => 'LinkedIn']);
        Job::factory()->create(['source' => 'Indeed']);
        Job::factory()->create(['source' => 'LinkedIn']);
        Job::factory()->create(['source' => 'larajobs']);

        $response = $this->getJson('/api/jobs/sources');

        $response->assertOk();
        $this->assertSame(['Indeed', 'LinkedIn', 'larajobs'], array_values($response->json()));
    }

    public function test_infojobs_never_appears_while_disabled(): void
    {
        Job::factory()->create(['source' => 'LinkedIn']);

        $response = $this->getJson('/api/jobs/sources');

        $this->assertNotContains('infojobs', $response->json());
    }
}

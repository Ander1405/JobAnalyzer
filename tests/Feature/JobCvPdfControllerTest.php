<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Job;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobCvPdfControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_404_when_the_job_has_no_tailored_cv(): void
    {
        $job = Job::factory()->create();

        $response = $this->get("/api/jobs/{$job->id}/cv/pdf");

        $response->assertNotFound();
    }

    public function test_it_streams_the_latest_variant_as_a_pdf(): void
    {
        $job = Job::factory()->create(['title' => 'Backend Engineer', 'company' => 'Acme']);
        Profile::factory()->create([
            'job_id' => $job->id,
            'raw_md' => "# Headline uno\n\n## Resumen\nPrimera versión.",
        ]);
        $latest = Profile::factory()->create([
            'job_id' => $job->id,
            'raw_md' => "# Headline dos\n\n## Resumen\nSegunda versión, la vigente.",
        ]);

        $response = $this->get("/api/jobs/{$job->id}/cv/pdf");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertSame($latest->id, $job->fresh()->latestCvVariant->id);
    }

    public function test_download_flag_sets_an_attachment_content_disposition(): void
    {
        $job = Job::factory()->create();
        Profile::factory()->create(['job_id' => $job->id]);

        $response = $this->get("/api/jobs/{$job->id}/cv/pdf?download=1");

        $response->assertOk();
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
    }
}

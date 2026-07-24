<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\TrackedJobStatus;
use App\Models\Job;
use App\Models\Profile;
use App\Models\TrackedJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTailorControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['jobhunter.claude_cli.binary' => base_path('tests/Fixtures/fake-claude-cli-tailor')]);
    }

    public function test_preview_dispatches_a_tailoring_job_whose_status_endpoint_returns_the_diff_preview_once_complete(): void
    {
        Profile::factory()->active()->create(['slug' => 'default']);
        $job = Job::factory()->create(['title' => 'Backend Engineer', 'company' => 'Acme']);

        $response = $this->postJson('/api/profile/tailor', [
            'job_id' => $job->id,
            'items' => ['Destacar experiencia con APIs', 'Priorizar Laravel'],
        ]);

        $response->assertStatus(202);
        $response->assertJsonPath('status', 'processing');

        // QUEUE_CONNECTION=sync in testing runs the TailorProfile job inline, so
        // by the time we poll, its result is already in cache.
        $status = $this->getJson('/api/profile/tailor/'.$response->json('request_id'));

        $status->assertOk();
        $status->assertJsonPath('status', 'completed');
        $status->assertJsonPath('overrides.headline', 'Desarrollador backend Laravel orientado a APIs');
        $status->assertJsonPath('overrides.skills', ['Laravel', 'PHP', 'Docker']);
        $this->assertStringContainsString('Desarrollador backend Laravel orientado a APIs', $status->json('after_markdown'));

        $this->assertSame(1, Profile::count());
    }

    public function test_status_returns_404_for_an_unknown_or_expired_request(): void
    {
        $response = $this->getJson('/api/profile/tailor/does-not-exist');

        $response->assertNotFound();
    }

    public function test_preview_requires_at_least_one_selected_item(): void
    {
        Profile::factory()->active()->create(['slug' => 'default']);
        $job = Job::factory()->create();

        $response = $this->postJson('/api/profile/tailor', [
            'job_id' => $job->id,
            'items' => [],
        ]);

        $response->assertUnprocessable();
    }

    public function test_preview_fails_gracefully_without_an_active_profile(): void
    {
        $job = Job::factory()->create();

        $response = $this->postJson('/api/profile/tailor', [
            'job_id' => $job->id,
            'items' => ['x'],
        ]);

        $response->assertUnprocessable();
    }

    public function test_confirm_creates_a_new_variant_named_after_the_company_and_title_without_touching_the_base(): void
    {
        $default = Profile::factory()->active()->create([
            'slug' => 'default',
            'headline' => 'Original headline',
        ]);
        $job = Job::factory()->create(['title' => 'Backend Engineer', 'company' => 'Acme']);

        $response = $this->postJson('/api/profile/tailor/confirm', [
            'job_id' => $job->id,
            'overrides' => [
                'headline' => 'Desarrollador backend Laravel orientado a APIs',
                'summary' => 'Nuevo resumen adaptado.',
                'experience' => ['Línea 1'],
                'skills' => ['Laravel', 'PHP'],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('slug', 'acme-backend-engineer');
        $response->assertJsonPath('is_active', false);

        $this->assertSame('Original headline', $default->fresh()->headline);
        $this->assertSame(2, Profile::count());
    }

    public function test_confirm_uniquifies_the_slug_when_tailoring_for_the_same_job_twice(): void
    {
        Profile::factory()->active()->create(['slug' => 'default']);
        $job = Job::factory()->create(['title' => 'Backend Engineer', 'company' => 'Acme']);

        $payload = [
            'job_id' => $job->id,
            'overrides' => [
                'headline' => 'x',
                'summary' => 'y',
                'experience' => [],
                'skills' => [],
            ],
        ];

        $this->postJson('/api/profile/tailor/confirm', $payload)->assertCreated();
        $second = $this->postJson('/api/profile/tailor/confirm', $payload);

        $second->assertCreated();
        $second->assertJsonPath('slug', 'acme-backend-engineer-2');
    }

    public function test_confirm_links_the_variant_to_the_job_and_marks_it_applied(): void
    {
        Profile::factory()->active()->create(['slug' => 'default']);
        $job = Job::factory()->create([
            'title' => 'Backend Engineer',
            'company' => 'Acme',
            'application_status' => ApplicationStatus::New,
        ]);

        $response = $this->postJson('/api/profile/tailor/confirm', [
            'job_id' => $job->id,
            'overrides' => [
                'headline' => 'x',
                'summary' => 'y',
                'experience' => [],
                'skills' => [],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('job_id', $job->id);

        $this->assertSame(ApplicationStatus::Applied, $job->fresh()->application_status);
    }

    public function test_confirm_does_not_downgrade_a_job_already_past_applied(): void
    {
        Profile::factory()->active()->create(['slug' => 'default']);
        $job = Job::factory()->create(['application_status' => ApplicationStatus::Interview]);

        $this->postJson('/api/profile/tailor/confirm', [
            'job_id' => $job->id,
            'overrides' => ['headline' => 'x', 'summary' => 'y', 'experience' => [], 'skills' => []],
        ])->assertCreated();

        $this->assertSame(ApplicationStatus::Interview, $job->fresh()->application_status);
    }

    public function test_confirm_marks_the_tracked_job_as_applied_and_stamps_applied_at(): void
    {
        Profile::factory()->active()->create(['slug' => 'default']);
        $job = Job::factory()->create();
        $trackedJob = TrackedJob::factory()->create([
            'job_id' => $job->id,
            'status' => TrackedJobStatus::SinAplicar,
        ]);

        $this->postJson('/api/profile/tailor/confirm', [
            'job_id' => $job->id,
            'overrides' => ['headline' => 'x', 'summary' => 'y', 'experience' => [], 'skills' => []],
        ])->assertCreated();

        $trackedJob->refresh();
        $this->assertSame(TrackedJobStatus::Aplique, $trackedJob->status);
        $this->assertNotNull($trackedJob->applied_at);
    }

    public function test_confirm_does_not_override_a_tracked_job_status_set_manually(): void
    {
        Profile::factory()->active()->create(['slug' => 'default']);
        $job = Job::factory()->create();
        $trackedJob = TrackedJob::factory()->create([
            'job_id' => $job->id,
            'status' => TrackedJobStatus::Rechazado,
        ]);

        $this->postJson('/api/profile/tailor/confirm', [
            'job_id' => $job->id,
            'overrides' => ['headline' => 'x', 'summary' => 'y', 'experience' => [], 'skills' => []],
        ])->assertCreated();

        $this->assertSame(TrackedJobStatus::Rechazado, $trackedJob->fresh()->status);
    }
}

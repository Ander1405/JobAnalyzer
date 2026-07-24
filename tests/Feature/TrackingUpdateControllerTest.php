<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CommentType;
use App\Enums\TrackedJobPriority;
use App\Enums\TrackedJobStatus;
use App\Models\TrackedJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingUpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_priority_and_next_action(): void
    {
        $trackedJob = TrackedJob::factory()->create();

        $response = $this->patchJson("/api/tracking/{$trackedJob->id}", [
            'priority' => TrackedJobPriority::Alta->value,
            'next_action' => 'Enviar seguimiento',
            'next_action_date' => '2026-08-01',
            'cv_version_used' => 'backend-senior-v2',
        ]);

        $response->assertOk();
        $response->assertJsonPath('priority', TrackedJobPriority::Alta->value);
        $response->assertJsonPath('next_action', 'Enviar seguimiento');
        $response->assertJsonPath('cv_version_used', 'backend-senior-v2');

        $this->assertDatabaseHas('tracked_jobs', [
            'id' => $trackedJob->id,
            'priority' => TrackedJobPriority::Alta->value,
        ]);
    }

    public function test_changing_the_status_creates_an_automatic_comment(): void
    {
        $trackedJob = TrackedJob::factory()->create(['status' => TrackedJobStatus::SinAplicar]);

        $response = $this->patchJson("/api/tracking/{$trackedJob->id}", [
            'status' => TrackedJobStatus::Aplique->value,
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', TrackedJobStatus::Aplique->value);
        $this->assertNotNull($trackedJob->fresh()->applied_at);

        $this->assertDatabaseHas('tracked_job_comments', [
            'tracked_job_id' => $trackedJob->id,
            'type' => CommentType::CambioEstado->value,
        ]);
    }

    public function test_it_rejects_an_invalid_status(): void
    {
        $trackedJob = TrackedJob::factory()->create();

        $response = $this->patchJson("/api/tracking/{$trackedJob->id}", [
            'status' => 'not-a-real-status',
        ]);

        $response->assertUnprocessable();
    }
}

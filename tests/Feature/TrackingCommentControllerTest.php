<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CommentType;
use App\Models\TrackedJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingCommentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_note_by_default(): void
    {
        $trackedJob = TrackedJob::factory()->create();

        $response = $this->postJson("/api/tracking/{$trackedJob->id}/comments", [
            'body' => 'Recruiter respondió por LinkedIn.',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('type', CommentType::Nota->value);
        $this->assertDatabaseHas('tracked_job_comments', [
            'tracked_job_id' => $trackedJob->id,
            'body' => 'Recruiter respondió por LinkedIn.',
            'type' => CommentType::Nota->value,
        ]);
    }

    public function test_it_accepts_an_explicit_type(): void
    {
        $trackedJob = TrackedJob::factory()->create();

        $response = $this->postJson("/api/tracking/{$trackedJob->id}/comments", [
            'body' => 'Entrevista técnica agendada.',
            'type' => CommentType::Entrevista->value,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('type', CommentType::Entrevista->value);
    }

    public function test_it_rejects_the_automatic_status_change_type(): void
    {
        $trackedJob = TrackedJob::factory()->create();

        $response = $this->postJson("/api/tracking/{$trackedJob->id}/comments", [
            'body' => 'Intentando falsear un cambio de estado.',
            'type' => CommentType::CambioEstado->value,
        ]);

        $response->assertUnprocessable();
    }

    public function test_it_requires_a_body(): void
    {
        $trackedJob = TrackedJob::factory()->create();

        $response = $this->postJson("/api/tracking/{$trackedJob->id}/comments", []);

        $response->assertUnprocessable();
    }
}

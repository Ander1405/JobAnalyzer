<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\CommentType;
use App\Enums\TrackedJobStatus;
use App\Models\Job;
use App\Models\TrackedJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackedJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracking_a_job_creates_a_tracked_job_in_sin_aplicar(): void
    {
        $job = Job::factory()->create();

        $trackedJob = TrackedJob::factory()->create(['job_id' => $job->id]);

        $this->assertSame(TrackedJobStatus::SinAplicar, $trackedJob->status);
        $this->assertTrue($job->trackedJob()->exists());
        $this->assertSame($job->id, $trackedJob->job->id);
    }

    public function test_changing_status_generates_an_automatic_comment(): void
    {
        $trackedJob = TrackedJob::factory()->create(['status' => TrackedJobStatus::SinAplicar]);

        $trackedJob->update(['status' => TrackedJobStatus::EnProceso]);

        $comment = $trackedJob->comments()->latest('id')->first();

        $this->assertNotNull($comment);
        $this->assertSame(CommentType::CambioEstado, $comment->type);
        $this->assertSame('Estado: Sin aplicar → En proceso', $comment->body);
    }

    public function test_transitioning_to_aplique_sets_applied_at(): void
    {
        $trackedJob = TrackedJob::factory()->create(['status' => TrackedJobStatus::SinAplicar]);

        $this->assertNull($trackedJob->applied_at);

        $trackedJob->update(['status' => TrackedJobStatus::Aplique]);

        $this->assertNotNull($trackedJob->fresh()->applied_at);
    }

    public function test_transitioning_away_from_status_without_change_does_not_create_a_comment(): void
    {
        $trackedJob = TrackedJob::factory()->create(['status' => TrackedJobStatus::SinAplicar]);

        $trackedJob->update(['priority' => 'alta']);

        $this->assertSame(0, $trackedJob->comments()->count());
    }

    public function test_manual_comments_can_be_added_alongside_automatic_ones(): void
    {
        $trackedJob = TrackedJob::factory()->create(['status' => TrackedJobStatus::SinAplicar]);

        $trackedJob->comments()->create([
            'body' => 'Envié seguimiento por correo.',
            'type' => CommentType::Seguimiento,
        ]);

        $trackedJob->update(['status' => TrackedJobStatus::EnProceso]);

        $this->assertSame(2, $trackedJob->comments()->count());
        $this->assertSame(
            [CommentType::Seguimiento, CommentType::CambioEstado],
            $trackedJob->comments()->orderBy('id')->pluck('type')->all(),
        );
    }
}

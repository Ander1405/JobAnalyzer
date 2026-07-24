<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CommentType;
use App\Enums\TrackedJobStatus;
use App\Models\TrackedJob;

class TrackedJobObserver
{
    public function updating(TrackedJob $trackedJob): void
    {
        if (! $trackedJob->isDirty('status')) {
            return;
        }

        /** @var TrackedJobStatus $previous */
        $previous = $trackedJob->getOriginal('status');
        $next = $trackedJob->status;

        $trackedJob->comments()->create([
            'body' => sprintf('Estado: %s → %s', $previous->label(), $next->label()),
            'type' => CommentType::CambioEstado,
        ]);

        if ($next === TrackedJobStatus::Aplique && $trackedJob->applied_at === null) {
            $trackedJob->setAttribute('applied_at', now());
        }
    }
}

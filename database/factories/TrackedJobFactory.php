<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TrackedJobStatus;
use App\Models\Job;
use App\Models\TrackedJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrackedJob>
 */
class TrackedJobFactory extends Factory
{
    protected $model = TrackedJob::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_id' => Job::factory(),
            'status' => TrackedJobStatus::SinAplicar,
            'priority' => null,
            'applied_at' => null,
            'cv_version_used' => null,
            'next_action' => null,
            'next_action_date' => null,
        ];
    }

    /**
     * A tracked job must belong to the same user as the job it tracks — resolved
     * after the job (and its own user) is created, so an explicit 'user_id' state
     * always wins but the default stays consistent with the parent job's owner.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (TrackedJob $trackedJob) {
            if (! $trackedJob->user_id) {
                $trackedJob->user_id = Job::find($trackedJob->job_id)?->user_id;
            }
        });
    }
}

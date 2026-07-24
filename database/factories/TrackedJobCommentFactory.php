<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CommentType;
use App\Models\TrackedJob;
use App\Models\TrackedJobComment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrackedJobComment>
 */
class TrackedJobCommentFactory extends Factory
{
    protected $model = TrackedJobComment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tracked_job_id' => TrackedJob::factory(),
            'body' => fake()->sentence(),
            'type' => CommentType::Nota,
        ];
    }
}

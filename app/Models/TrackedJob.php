<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TrackedJobPriority;
use App\Enums\TrackedJobStatus;
use App\Models\Concerns\BelongsToUser;
use Database\Factories\TrackedJobFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $job_id
 * @property TrackedJobStatus $status
 * @property TrackedJobPriority|null $priority
 * @property Carbon|null $applied_at
 * @property string|null $cv_version_used
 * @property string|null $next_action
 * @property Carbon|null $next_action_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TrackedJobComment|null $latestComment
 */
#[Fillable([
    'user_id',
    'job_id',
    'status',
    'priority',
    'applied_at',
    'cv_version_used',
    'next_action',
    'next_action_date',
])]
class TrackedJob extends Model
{
    /** @use HasFactory<TrackedJobFactory> */
    use BelongsToUser, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TrackedJobStatus::class,
            'priority' => TrackedJobPriority::class,
            'applied_at' => 'datetime',
            'next_action_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Job, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * @return HasMany<TrackedJobComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TrackedJobComment::class);
    }

    /**
     * @return HasOne<TrackedJobComment, $this>
     */
    public function latestComment(): HasOne
    {
        return $this->hasOne(TrackedJobComment::class)->latestOfMany();
    }
}

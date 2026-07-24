<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CommentType;
use Database\Factories\TrackedJobCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tracked_job_id
 * @property string $body
 * @property CommentType $type
 * @property Carbon|null $created_at
 */
#[Fillable(['tracked_job_id', 'body', 'type'])]
class TrackedJobComment extends Model
{
    /** @use HasFactory<TrackedJobCommentFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CommentType::class,
        ];
    }

    /**
     * @return BelongsTo<TrackedJob, $this>
     */
    public function trackedJob(): BelongsTo
    {
        return $this->belongsTo(TrackedJob::class);
    }
}

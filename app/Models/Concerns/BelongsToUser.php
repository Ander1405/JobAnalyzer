<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Enforces per-user isolation: a signed-in non-admin only ever sees and creates
 * rows scoped to their own account. Console/queue contexts have no authenticated
 * user, so the scope simply no-ops there — the caller is expected to bind one
 * explicitly (see Auth::onceUsingId in the jobs:* commands) when it needs isolation.
 */
trait BelongsToUser
{
    public static function bootBelongsToUser(): void
    {
        static::addGlobalScope('owner', function (Builder $query) {
            if (auth()->check() && ! auth()->user()->hasRole('admin')) {
                $query->where($query->getModel()->getTable().'.user_id', auth()->id());
            }
        });

        static::creating(function ($model) {
            if (empty($model->user_id) && auth()->check()) {
                $model->user_id = auth()->id();
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Console commands run with no authenticated user, but job_offers/profiles/tracked_jobs
 * are now per-user (BelongsToUser scope). Every command that touches them needs an
 * explicit --user=<email> so the scope and the auto-fill-on-create hook resolve to the
 * right account instead of silently seeing/writing nothing (or, for an admin account,
 * everything).
 */
trait RequiresUserContext
{
    private function resolveUserOption(): ?User
    {
        $email = $this->option('user');

        if (! $email) {
            $this->error('Falta --user=<email>: este comando opera sobre datos de un usuario específico.');

            return null;
        }

        $user = User::where('email', $email)->first();

        if ($user === null) {
            $this->error("No existe ningún usuario con el email [{$email}].");

            return null;
        }

        Auth::onceUsingId($user->id);

        return $user;
    }
}

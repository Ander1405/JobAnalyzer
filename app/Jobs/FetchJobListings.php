<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;

/**
 * Fetching hits three job boards with retries; running it inside the HTTP request
 * blew past PHP-FPM's 30s max_execution_time and killed the whole run halfway
 * through. It also let the AI provider run under PHP-FPM, where the Claude CLI
 * has no session (see config/jobhunter.php) — the queue worker has both.
 */
class FetchJobListings implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public readonly int $userId) {}

    public function handle(): void
    {
        $user = User::findOrFail($this->userId);

        Artisan::call('jobs:fetch', ['--queue' => true, '--user' => $user->email]);
    }
}

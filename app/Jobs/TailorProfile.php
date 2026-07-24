<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Job as JobListing;
use App\Models\Profile;
use App\Services\AI\CvTailorer;
use App\Services\Profile\ProfileBuilder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Runs CvTailorer off the web request so it goes through a queue worker
 * instead of PHP-FPM — same reason AnalyzeJobListing exists: on macOS with
 * claude_cli, only a process spawned from an interactive terminal (i.e. a
 * `queue:work` you run yourself) can read the Keychain session.
 */
class TailorProfile implements ShouldQueue
{
    use Queueable;

    /** An AI call can legitimately take a couple of minutes; the queue default of 60s cannot. */
    public int $timeout = 300;

    public int $tries = 1;

    /** @param  array<int, string>  $items */
    public function __construct(
        public readonly string $requestId,
        public readonly int $profileId,
        public readonly int $jobId,
        public readonly array $items,
        public readonly int $userId,
    ) {
        $this->onQueue('analysis');
    }

    public function handle(CvTailorer $tailorer, ProfileBuilder $builder): void
    {
        // See AnalyzeJobListing: the queue worker has no authenticated user of its
        // own, so the BelongsToUser scope needs this bound explicitly.
        Auth::onceUsingId($this->userId);

        $profile = Profile::query()->findOrFail($this->profileId);
        $job = JobListing::query()->findOrFail($this->jobId);

        $result = $tailorer->tailor($profile, $job, $this->items);

        $overrides = [
            'headline' => $result->headline,
            'summary' => $result->summary,
            'experience' => $result->experience,
            'skills' => $result->skills,
        ];

        $afterMarkdown = $builder->toMarkdown([
            'contact' => $profile->contact ?? [],
            'headline' => $overrides['headline'],
            'summary' => $overrides['summary'],
            'experience' => $overrides['experience'],
            'skills' => $overrides['skills'],
            'education' => $profile->education ?? [],
            'languages' => $profile->languages ?? ['items' => [], 'english_level' => null],
            'certifications' => $profile->certifications ?? [],
        ]);

        Cache::put(self::cacheKey($this->requestId), [
            'status' => 'completed',
            'job_id' => $job->id,
            'overrides' => $overrides,
            'before_markdown' => $profile->raw_md,
            'after_markdown' => $afterMarkdown,
            'usage' => [
                'durationMs' => $result->usage->durationMs,
                'inputTokens' => $result->usage->inputTokens,
                'outputTokens' => $result->usage->outputTokens,
                'costUsd' => $result->usage->costUsd,
            ],
            'model' => $result->model,
        ], now()->addMinutes(15));
    }

    /**
     * Without this the preview stays "processing" forever whenever the worker
     * kills the job (timeout, fatal error), and the UI polls it indefinitely.
     */
    public function failed(?Throwable $exception): void
    {
        Cache::put(self::cacheKey($this->requestId), [
            'status' => 'failed',
            'message' => $exception?->getMessage() ?? 'No se pudo adaptar el CV.',
        ], now()->addMinutes(15));
    }

    public static function cacheKey(string $requestId): string
    {
        return "tailor-request:{$requestId}";
    }
}

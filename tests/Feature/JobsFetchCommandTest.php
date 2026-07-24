<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTOs\JobOffer;
use App\Enums\JobStatus;
use App\Models\Job;
use App\Services\Sources\InfoJobsFetcher;
use App\Services\Sources\JobSourceInterface;
use App\Services\Sources\JSearchFetcher;
use App\Services\Sources\LaraJobsRssFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class JobsFetchCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_falls_back_to_the_listing_url_when_a_source_has_no_apply_url(): void
    {
        $this->app->bind(JSearchFetcher::class, fn () => new class implements JobSourceInterface
        {
            public function fetch(): Collection
            {
                return collect([new JobOffer(
                    source: 'jsearch',
                    company: 'Acme Corp',
                    title: 'Senior PHP Developer',
                    description: 'Laravel expert needed.',
                    url: 'https://example.com/jobs/123',
                    applyUrl: null,
                )]);
            }
        });
        $this->app->bind(LaraJobsRssFetcher::class, fn () => new class implements JobSourceInterface
        {
            public function fetch(): Collection
            {
                return collect();
            }
        });
        $this->app->bind(InfoJobsFetcher::class, fn () => new class implements JobSourceInterface
        {
            public function fetch(): Collection
            {
                return collect();
            }
        });

        $this->artisan('jobs:fetch', ['--user' => $this->actingUser->email])->assertSuccessful();

        $job = Job::sole();
        $this->assertSame('https://example.com/jobs/123', $job->apply_url);
        $this->assertSame('https://example.com/jobs/123', $job->url);
    }

    public function test_it_automatically_analyzes_new_jobs_when_fetched(): void
    {
        $this->app->bind(JSearchFetcher::class, fn () => new class implements JobSourceInterface
        {
            public function fetch(): Collection
            {
                return collect([new JobOffer(
                    source: 'jsearch',
                    company: 'Acme Corp',
                    title: 'PHP Developer',
                    description: 'Laravel expert needed.',
                    url: 'https://example.com/jobs/456',
                )]);
            }
        });
        $this->app->bind(LaraJobsRssFetcher::class, fn () => new class implements JobSourceInterface
        {
            public function fetch(): Collection
            {
                return collect();
            }
        });
        $this->app->bind(InfoJobsFetcher::class, fn () => new class implements JobSourceInterface
        {
            public function fetch(): Collection
            {
                return collect();
            }
        });

        $this->artisan('jobs:fetch', ['--user' => $this->actingUser->email])->assertSuccessful();

        $job = Job::sole();
        $this->assertSame(JobStatus::Analyzed, $job->status);
    }
}

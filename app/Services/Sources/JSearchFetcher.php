<?php

declare(strict_types=1);

namespace App\Services\Sources;

use App\DTOs\JobOffer;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class JSearchFetcher implements JobSourceInterface
{
    private const ENDPOINT = 'https://jsearch.p.rapidapi.com/search-v2';

    /**
     * One query per HTTP round trip (each with its own timeout+retry budget), so
     * fetching them one at a time serialized their worst case: 3 queries could
     * each wait out 30s x 3 retries before the next even started. Http::pool
     * fires them concurrently — total wall time is the slowest query, not the sum.
     */
    public function fetch(): Collection
    {
        /** @var array<int, string> $queries */
        $queries = config('jobhunter.job_search_queries');
        $country = (string) config('jobhunter.job_search_country');

        if ($queries === []) {
            return collect();
        }

        /** @var array<string, Response|Throwable> $responses */
        $responses = Http::pool(fn (Pool $pool) => collect($queries)
            ->map(fn (string $query) => $pool->as($query)
                ->withHeaders([
                    'X-RapidAPI-Key' => config('jobhunter.rapidapi_key'),
                    'X-RapidAPI-Host' => 'jsearch.p.rapidapi.com',
                ])
                ->timeout(30)
                ->retry(3, 2000)
                ->get(self::ENDPOINT, [
                    'query' => $query,
                    'country' => $country,
                    'date_posted' => 'week',
                ]))
            ->all());

        return collect($queries)
            ->flatMap(fn (string $query) => $this->parseResponse($query, $responses[$query]));
    }

    /**
     * @return Collection<int, JobOffer>
     */
    private function parseResponse(string $query, Response|Throwable $response): Collection
    {
        if ($response instanceof Throwable) {
            Log::error("JSearch query [{$query}] failed to fetch.", ['exception' => $response->getMessage()]);

            return collect();
        }

        $response->throw();

        /** @var array<int, array<string, mixed>> $jobs */
        $jobs = $response->json('data.jobs', []);

        return collect($jobs)
            ->map(fn (array $job) => new JobOffer(
                source: $this->publisher($job),
                company: (string) ($job['employer_name'] ?? ''),
                title: (string) ($job['job_title'] ?? ''),
                description: (string) ($job['job_description'] ?? ''),
                url: (string) ($job['job_apply_link'] ?? ''),
                contractType: $job['job_employment_type'] ?? null,
                salaryRaw: $this->normalizeSalary($job),
                applyUrl: $this->nullableString($job['job_apply_link'] ?? null),
                location: $this->location($job),
                isRemote: isset($job['job_is_remote']) ? (bool) $job['job_is_remote'] : null,
                workMode: isset($job['job_is_remote']) && $job['job_is_remote'] ? 'Remoto' : null,
                employmentType: $this->nullableString($job['job_employment_type'] ?? null),
                postedAt: $this->nullableString($job['job_posted_at_datetime_utc'] ?? null),
                expiresAt: $this->nullableString($job['job_offer_expiration_datetime_utc'] ?? null),
                companyLogo: $this->nullableString($job['employer_logo'] ?? null),
                companyWebsite: $this->nullableString($job['employer_website'] ?? null),
                benefits: $this->stringList($job['job_highlights']['Benefits'] ?? null),
                requiredSkills: $this->stringList($job['job_highlights']['Qualifications'] ?? null),
            ));
    }

    /**
     * @param  array<string, mixed>  $job
     */
    private function location(array $job): ?string
    {
        $city = trim((string) ($job['job_city'] ?? ''));
        $country = trim((string) ($job['job_country'] ?? ''));

        return collect([$city, $country])->filter()->implode(', ') ?: null;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @return array<int, string>|null
     */
    private function stringList(mixed $value): ?array
    {
        if (! is_array($value) || $value === []) {
            return null;
        }

        return array_values(array_map('strval', $value));
    }

    /**
     * JSearch aggregates listings from many job boards (LinkedIn, Indeed, Glassdoor,
     * Trabajo.org, ...); job_publisher is the real originating site, which is far more
     * useful to show/filter by than the generic "jsearch" pipeline name.
     *
     * @param  array<string, mixed>  $job
     */
    private function publisher(array $job): string
    {
        $publisher = trim((string) ($job['job_publisher'] ?? ''));

        return $publisher !== '' ? $publisher : 'JSearch';
    }

    /**
     * @param  array<string, mixed>  $job
     */
    private function normalizeSalary(array $job): ?string
    {
        if (! empty($job['job_salary_string'])) {
            return (string) $job['job_salary_string'];
        }

        $min = $job['job_min_salary'] ?? null;
        $max = $job['job_max_salary'] ?? null;
        $currency = $job['job_salary_currency'] ?? null;

        if ($min === null && $max === null) {
            return null;
        }

        return trim(sprintf('%s-%s %s', $min ?? '?', $max ?? '?', $currency ?? ''));
    }
}

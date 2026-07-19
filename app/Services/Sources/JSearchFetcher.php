<?php

declare(strict_types=1);

namespace App\Services\Sources;

use App\DTOs\JobOffer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class JSearchFetcher implements JobSourceInterface
{
    private const ENDPOINT = 'https://jsearch.p.rapidapi.com/search-v2';

    public function fetch(): Collection
    {
        /** @var array<int, string> $queries */
        $queries = config('jobhunter.job_search_queries');
        $country = (string) config('jobhunter.job_search_country');

        return collect($queries)
            ->flatMap(fn (string $query) => $this->fetchQuery($query, $country));
    }

    /**
     * @return Collection<int, JobOffer>
     */
    private function fetchQuery(string $query, string $country): Collection
    {
        $response = Http::withHeaders([
            'X-RapidAPI-Key' => config('jobhunter.rapidapi_key'),
            'X-RapidAPI-Host' => 'jsearch.p.rapidapi.com',
        ])
            ->timeout(30)
            ->retry(3, 2000)
            ->get(self::ENDPOINT, [
                'query' => $query,
                'country' => $country,
                'date_posted' => 'week',
            ]);

        $response->throw();

        /** @var array<int, array<string, mixed>> $jobs */
        $jobs = $response->json('data.jobs', []);

        return collect($jobs)
            ->map(fn (array $job) => new JobOffer(
                source: 'jsearch',
                company: (string) ($job['employer_name'] ?? ''),
                title: (string) ($job['job_title'] ?? ''),
                description: (string) ($job['job_description'] ?? ''),
                url: (string) ($job['job_apply_link'] ?? ''),
                contractType: $job['job_employment_type'] ?? null,
                salaryRaw: $this->normalizeSalary($job),
            ));
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

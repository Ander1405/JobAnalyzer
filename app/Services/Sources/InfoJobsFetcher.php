<?php

declare(strict_types=1);

namespace App\Services\Sources;

use App\DTOs\JobOffer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InfoJobsFetcher implements JobSourceInterface
{
    private const ENDPOINT = 'https://api.infojobs.net/api/9/offer';

    public function fetch(): Collection
    {
        if (! config('jobhunter.infojobs.enabled')) {
            // Turning the source off is a decision, not an incident: at warning
            // level it filed itself next to the real failures on every run.
            Log::debug('InfoJobs source is disabled (INFOJOBS_ENABLED=false); skipping fetch.');

            return collect();
        }

        /** @var array<int, string> $queries */
        $queries = config('jobhunter.job_search_queries');

        $response = Http::withBasicAuth(
            (string) config('jobhunter.infojobs.client_id'),
            (string) config('jobhunter.infojobs.client_secret'),
        )
            ->timeout(30)
            ->retry(3, 2000)
            ->get(self::ENDPOINT, [
                'q' => implode(' ', $queries),
                'country' => (string) config('jobhunter.job_search_country'),
            ]);

        $response->throw();

        /** @var array<int, array<string, mixed>> $offers */
        $offers = $response->json('offers', []);

        return collect($offers)
            ->map(fn (array $offer) => new JobOffer(
                source: 'infojobs',
                company: (string) ($offer['author']['name'] ?? ''),
                title: (string) ($offer['title'] ?? ''),
                description: (string) ($offer['requirementMin'] ?? ''),
                url: (string) ($offer['link'] ?? ''),
                contractType: $offer['contractType']['value'] ?? null,
                salaryRaw: $offer['salaryDescription'] ?? null,
                applyUrl: $this->nullableString($offer['link'] ?? null),
                location: $this->location($offer),
                employmentType: $this->nullableString($offer['contractType']['value'] ?? null),
                postedAt: $this->nullableString($offer['updated'] ?? $offer['published'] ?? null),
            ));
    }

    /**
     * @param  array<string, mixed>  $offer
     */
    private function location(array $offer): ?string
    {
        $city = trim((string) ($offer['city'] ?? ''));
        $province = trim((string) ($offer['province']['value'] ?? ''));

        return collect([$city, $province])->filter()->unique()->implode(', ') ?: null;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

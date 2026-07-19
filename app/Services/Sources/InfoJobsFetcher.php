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
            Log::warning('InfoJobs source is disabled (INFOJOBS_ENABLED=false); skipping fetch.');

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
            ));
    }
}

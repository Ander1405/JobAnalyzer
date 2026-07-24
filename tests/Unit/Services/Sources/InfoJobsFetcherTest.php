<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Sources;

use App\Services\Sources\InfoJobsFetcher;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InfoJobsFetcherTest extends TestCase
{
    public function test_it_maps_the_infojobs_response_into_job_offers(): void
    {
        config([
            'jobhunter.infojobs.enabled' => true,
            'jobhunter.infojobs.client_id' => 'test-id',
            'jobhunter.infojobs.client_secret' => 'test-secret',
            'jobhunter.job_search_queries' => ['laravel developer'],
            'jobhunter.job_search_country' => 'co',
        ]);

        Http::fake([
            'api.infojobs.net/*' => Http::response([
                'offers' => [
                    [
                        'author' => ['name' => 'Acme Corp'],
                        'title' => 'Backend Developer',
                        'requirementMin' => 'Laravel experience required.',
                        'link' => 'https://infojobs.example.com/offer/1',
                        'contractType' => ['value' => 'Indefinido'],
                        'salaryDescription' => '4.000-6.000 EUR',
                        'city' => 'Madrid',
                        'province' => ['value' => 'Madrid'],
                        'updated' => '2026-07-10T12:00:00.000Z',
                    ],
                ],
            ], 200),
        ]);

        $offers = (new InfoJobsFetcher)->fetch();

        $this->assertCount(1, $offers);

        $offer = $offers->first();
        $this->assertSame('infojobs', $offer->source);
        $this->assertSame('Acme Corp', $offer->company);
        $this->assertSame('https://infojobs.example.com/offer/1', $offer->url);
        $this->assertSame('https://infojobs.example.com/offer/1', $offer->applyUrl);
        $this->assertSame('Madrid', $offer->location);
        $this->assertSame('Indefinido', $offer->employmentType);
        $this->assertSame('2026-07-10T12:00:00.000Z', $offer->postedAt);
    }

    public function test_it_skips_fetching_when_disabled(): void
    {
        config(['jobhunter.infojobs.enabled' => false]);

        $offers = (new InfoJobsFetcher)->fetch();

        $this->assertCount(0, $offers);
    }
}

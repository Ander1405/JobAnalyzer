<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Sources;

use App\Services\Sources\JSearchFetcher;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class JSearchFetcherTest extends TestCase
{
    public function test_it_maps_the_jsearch_response_into_job_offers(): void
    {
        config([
            'jobhunter.job_search_queries' => ['laravel developer'],
            'jobhunter.job_search_country' => 'co',
            'jobhunter.rapidapi_key' => 'test-key',
        ]);

        Http::fake([
            'jsearch.p.rapidapi.com/*' => Http::response(
                json_decode(file_get_contents(base_path('tests/Fixtures/jsearch-response.json')), true),
                200,
            ),
        ]);

        $offers = (new JSearchFetcher)->fetch();

        $this->assertCount(2, $offers);

        $first = $offers->first();
        $this->assertSame('jsearch', $first->source);
        $this->assertSame('Acme Corp', $first->company);
        $this->assertSame('Senior PHP Developer', $first->title);
        $this->assertSame('https://example.com/jobs/123', $first->url);
        $this->assertSame('FULLTIME', $first->contractType);
        $this->assertSame('4000-6000 USD', $first->salaryRaw);

        $second = $offers->last();
        $this->assertNull($second->salaryRaw);
    }
}

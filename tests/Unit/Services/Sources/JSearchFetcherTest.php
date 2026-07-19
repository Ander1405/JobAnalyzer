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
        $this->assertSame('4K-6K a month', $first->salaryRaw);

        $second = $offers->last();
        $this->assertNull($second->salaryRaw);
    }

    public function test_it_hits_the_search_v2_endpoint(): void
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

        (new JSearchFetcher)->fetch();

        Http::assertSent(fn ($request) => $request->url() === 'https://jsearch.p.rapidapi.com/search-v2?query=laravel%20developer&country=co&date_posted=week');
    }

    public function test_it_falls_back_to_min_max_salary_when_job_salary_string_is_absent(): void
    {
        config([
            'jobhunter.job_search_queries' => ['laravel developer'],
            'jobhunter.job_search_country' => 'co',
            'jobhunter.rapidapi_key' => 'test-key',
        ]);

        Http::fake([
            'jsearch.p.rapidapi.com/*' => Http::response([
                'status' => 'OK',
                'data' => [
                    'jobs' => [
                        [
                            'employer_name' => 'Acme Corp',
                            'job_title' => 'Senior PHP Developer',
                            'job_description' => 'Laravel expert needed.',
                            'job_apply_link' => 'https://example.com/jobs/123',
                            'job_employment_type' => 'FULLTIME',
                            'job_min_salary' => 4000,
                            'job_max_salary' => 6000,
                            'job_salary_currency' => 'USD',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $offers = (new JSearchFetcher)->fetch();

        $this->assertSame('4000-6000 USD', $offers->first()->salaryRaw);
    }
}

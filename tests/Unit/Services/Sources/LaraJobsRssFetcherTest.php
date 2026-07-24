<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Sources;

use App\Services\Sources\LaraJobsRssFetcher;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LaraJobsRssFetcherTest extends TestCase
{
    public function test_it_maps_feed_items_using_the_job_namespace_fields(): void
    {
        Http::fake([
            'larajobs.com/feed' => Http::response(
                file_get_contents(base_path('tests/Fixtures/larajobs-feed.xml')),
                200,
                ['Content-Type' => 'application/xml'],
            ),
        ]);

        $offers = (new LaraJobsRssFetcher)->fetch();

        $this->assertCount(2, $offers);

        $first = $offers->first();
        $this->assertSame('larajobs', $first->source);
        $this->assertSame('HelpBnk', $first->company);
        $this->assertSame('Senior Laravel Engineer', $first->title);
        $this->assertSame('CONTRACTOR', $first->contractType);
        $this->assertSame('https://larajobs.com/job/3905', $first->url);
        $this->assertStringContainsString('Location: Remote / Europe', $first->description);
        $this->assertStringContainsString('Tags: MySQL,PHP,React,Redis', $first->description);
        $this->assertSame('https://larajobs.com/job/3905', $first->applyUrl);
        $this->assertSame('Remote / Europe', $first->location);
        $this->assertTrue($first->isRemote);
        $this->assertSame('Remoto', $first->workMode);
    }

    public function test_it_falls_back_to_splitting_the_title_when_job_company_is_empty(): void
    {
        Http::fake([
            'larajobs.com/feed' => Http::response(
                file_get_contents(base_path('tests/Fixtures/larajobs-feed.xml')),
                200,
                ['Content-Type' => 'application/xml'],
            ),
        ]);

        $offers = (new LaraJobsRssFetcher)->fetch();
        $legacy = $offers->last();

        $this->assertSame('Legacy Corp', $legacy->company);
        $this->assertSame('Backend Developer', $legacy->title);
        $this->assertSame('We need a backend developer with Laravel experience.', $legacy->description);
        $this->assertNull($legacy->location);
        $this->assertNull($legacy->isRemote);
        $this->assertNull($legacy->workMode);
    }
}

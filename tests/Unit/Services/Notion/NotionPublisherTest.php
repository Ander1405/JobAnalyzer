<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Notion;

use App\Enums\JobStatus;
use App\Models\Job;
use App\Services\Notion\NotionPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotionPublisherTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_chunks_a_long_description_into_blocks_of_at_most_1900_characters(): void
    {
        config([
            'jobhunter.notion.token' => 'test-token',
            'jobhunter.notion.database_id' => 'test-database-id',
        ]);

        Http::fake([
            'api.notion.com/*' => Http::response(['id' => 'notion-page-123'], 200),
        ]);

        $job = Job::factory()->analyzed()->create([
            'description' => str_repeat('a', 4400),
        ]);

        (new NotionPublisher)->publish($job);

        $request = Http::recorded()[0][0];
        $body = $request->data();

        $this->assertSame('https://api.notion.com/v1/pages', $request->url());
        $this->assertSame('2022-06-28', $request->header('Notion-Version')[0]);

        $this->assertArrayHasKey('Cargo', $body['properties']);
        $this->assertArrayHasKey('Empresa', $body['properties']);
        $this->assertArrayHasKey('Fuente', $body['properties']);
        $this->assertArrayHasKey('URL', $body['properties']);
        $this->assertArrayHasKey('Match %', $body['properties']);
        $this->assertArrayHasKey('Tipo de contrato', $body['properties']);
        $this->assertArrayHasKey('Salario', $body['properties']);
        $this->assertArrayHasKey('Moneda', $body['properties']);
        $this->assertArrayHasKey('Idioma', $body['properties']);
        $this->assertArrayHasKey('Inglés requerido', $body['properties']);
        $this->assertArrayHasKey('Alerta inglés', $body['properties']);
        $this->assertArrayHasKey('Estado', $body['properties']);
        $this->assertArrayHasKey('Fecha detectada', $body['properties']);
        $this->assertSame('Nueva', $body['properties']['Estado']['select']['name']);
        $this->assertFalse($body['properties']['Alerta inglés']['checkbox']);

        $descriptionParagraphs = collect($body['children'])
            ->filter(fn (array $block) => $block['type'] === 'paragraph');

        $this->assertCount(3, $descriptionParagraphs);

        $lengths = $descriptionParagraphs
            ->map(fn (array $block) => mb_strlen($block['paragraph']['rich_text'][0]['text']['content']))
            ->values();

        $this->assertSame([1900, 1900, 600], $lengths->all());

        $job->refresh();
        $this->assertSame(JobStatus::Published, $job->status);
        $this->assertSame('notion-page-123', $job->notion_page_id);
    }

    public function test_it_adds_a_red_flags_section_only_when_there_are_flags(): void
    {
        config([
            'jobhunter.notion.token' => 'test-token',
            'jobhunter.notion.database_id' => 'test-database-id',
        ]);

        Http::fake([
            'api.notion.com/*' => Http::response(['id' => 'notion-page-123'], 200),
        ]);

        $job = Job::factory()->analyzed()->create([
            'ai_analysis' => array_merge(Job::factory()->analyzed()->make()->ai_analysis, [
                'red_flags' => ['El stack no coincide con el perfil.'],
            ]),
        ]);

        (new NotionPublisher)->publish($job);

        $body = Http::recorded()[0][0]->data();
        $headings = collect($body['children'])
            ->filter(fn (array $block) => $block['type'] === 'heading_2')
            ->map(fn (array $block) => $block['heading_2']['rich_text'][0]['text']['content']);

        $this->assertTrue($headings->contains('🚩 Red flags'));
    }

    public function test_it_omits_the_red_flags_section_when_there_are_none(): void
    {
        config([
            'jobhunter.notion.token' => 'test-token',
            'jobhunter.notion.database_id' => 'test-database-id',
        ]);

        Http::fake([
            'api.notion.com/*' => Http::response(['id' => 'notion-page-123'], 200),
        ]);

        $job = Job::factory()->analyzed()->create();

        (new NotionPublisher)->publish($job);

        $body = Http::recorded()[0][0]->data();
        $headings = collect($body['children'])
            ->filter(fn (array $block) => $block['type'] === 'heading_2')
            ->map(fn (array $block) => $block['heading_2']['rich_text'][0]['text']['content']);

        $this->assertFalse($headings->contains('🚩 Red flags'));
    }

    public function test_is_eligible_respects_the_min_match_to_publish_threshold(): void
    {
        config(['jobhunter.min_match_to_publish' => 75]);
        $publisher = new NotionPublisher;

        $high = Job::factory()->analyzed()->make(['ai_analysis' => ['match_score' => 80]]);
        $low = Job::factory()->analyzed()->make(['ai_analysis' => ['match_score' => 60]]);
        $unanalyzed = Job::factory()->make(['ai_analysis' => null]);

        $this->assertTrue($publisher->isEligible($high));
        $this->assertFalse($publisher->isEligible($low));
        $this->assertFalse($publisher->isEligible($unanalyzed));
    }
}

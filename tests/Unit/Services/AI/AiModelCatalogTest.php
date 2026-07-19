<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use App\Services\AI\AiModelCatalog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiModelCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('ai_models:gemini');
        Cache::forget('ai_models:openrouter');
    }

    public function test_it_lists_the_three_known_providers(): void
    {
        $providers = array_column((new AiModelCatalog)->providers(), 'id');

        $this->assertSame(['claude_cli', 'gemini', 'openrouter'], $providers);
    }

    public function test_it_returns_a_static_curated_list_for_claude_cli_without_any_http_call(): void
    {
        Http::fake(function () {
            $this->fail('claude_cli models should never make an HTTP call.');
        });

        $models = (new AiModelCatalog)->modelsFor('claude_cli');

        $this->assertNotEmpty($models);
        $this->assertContains('default', array_column($models, 'id'));
        $this->assertTrue($models[0]['free']);
    }

    public function test_it_filters_gemini_models_to_chat_capable_ones(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'models' => [
                    [
                        'name' => 'models/gemini-flash-latest',
                        'displayName' => 'Gemini Flash Latest',
                        'supportedGenerationMethods' => ['generateContent'],
                    ],
                    [
                        'name' => 'models/gemini-2.5-flash-preview-tts',
                        'displayName' => 'Gemini TTS',
                        'supportedGenerationMethods' => ['generateContent'],
                    ],
                    [
                        'name' => 'models/embedding-001',
                        'displayName' => 'Embedding',
                        'supportedGenerationMethods' => ['embedContent'],
                    ],
                ],
            ], 200),
        ]);

        $models = (new AiModelCatalog)->modelsFor('gemini');

        $this->assertCount(1, $models);
        $this->assertSame('gemini-flash-latest', $models[0]['id']);
        $this->assertSame('Gemini Flash Latest', $models[0]['label']);
    }

    public function test_it_sorts_openrouter_models_with_free_ones_first_and_maps_pricing(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'data' => [
                    [
                        'id' => 'vendor/paid-model',
                        'name' => 'Paid Model',
                        'pricing' => ['prompt' => '0.000002', 'completion' => '0.000004'],
                    ],
                    [
                        'id' => 'vendor/free-model:free',
                        'name' => 'Free Model',
                        'pricing' => ['prompt' => '0', 'completion' => '0'],
                    ],
                ],
            ], 200),
        ]);

        $models = (new AiModelCatalog)->modelsFor('openrouter');

        $this->assertSame('vendor/free-model:free', $models[0]['id']);
        $this->assertTrue($models[0]['free']);
        $this->assertFalse($models[1]['free']);
        $this->assertSame(0.000002, $models[1]['promptPrice']);
    }
}

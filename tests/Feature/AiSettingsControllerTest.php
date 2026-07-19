<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_current_settings_seeded_from_config(): void
    {
        $response = $this->getJson('/api/ai/settings');

        $response->assertOk()->assertJson([
            'provider' => 'claude_cli',
            'model' => null,
        ]);
    }

    public function test_it_lists_the_known_providers(): void
    {
        $response = $this->getJson('/api/ai/providers');

        $response->assertOk();
        $this->assertSame(['claude_cli', 'gemini', 'openrouter'], array_column($response->json(), 'id'));
    }

    public function test_it_updates_and_persists_the_provider_and_model(): void
    {
        $response = $this->putJson('/api/ai/settings', [
            'provider' => 'openrouter',
            'model' => 'some-model:free',
        ]);

        $response->assertOk()->assertJson([
            'provider' => 'openrouter',
            'model' => 'some-model:free',
        ]);

        $this->getJson('/api/ai/settings')->assertJson([
            'provider' => 'openrouter',
            'model' => 'some-model:free',
        ]);
    }

    public function test_it_rejects_an_unknown_provider(): void
    {
        $response = $this->putJson('/api/ai/settings', ['provider' => 'bogus']);

        $response->assertUnprocessable()->assertJsonValidationErrors('provider');
    }

    public function test_it_rejects_an_unknown_provider_when_listing_models(): void
    {
        $response = $this->getJson('/api/ai/providers/bogus/models');

        $response->assertUnprocessable();
    }
}

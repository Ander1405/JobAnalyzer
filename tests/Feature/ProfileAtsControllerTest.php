<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileAtsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['jobhunter.claude_cli.binary' => base_path('tests/Fixtures/fake-claude-cli-ats')]);
    }

    public function test_analyze_returns_a_score_and_actionable_problems(): void
    {
        Profile::factory()->active()->create(['slug' => 'default']);

        $response = $this->postJson('/api/profile/ats');

        $response->assertOk();
        $response->assertJsonPath('ats_score', 72);
        $response->assertJsonCount(1, 'problemas');
        $response->assertJsonPath('keywords_faltantes', ['CI/CD', 'AWS']);
        $this->assertStringContainsString('# Jane Doe', $response->json('after_markdown'));
    }

    public function test_analyze_fails_gracefully_without_an_active_profile(): void
    {
        $response = $this->postJson('/api/profile/ats');

        $response->assertUnprocessable();
    }

    public function test_confirm_parses_the_optimized_markdown_into_a_new_variant_without_touching_the_base(): void
    {
        $default = Profile::factory()->active()->create([
            'slug' => 'default',
            'headline' => 'Original headline',
        ]);

        $markdown = "# Jane Doe\n\n## Resumen\nResumen optimizado.\n\n## Experiencia\n- Backend developer en Acme.\n\n## Skills\n- Laravel\n- PHP\n\n## Educación\nNo especificado\n\n## Idiomas\nNo especificado\n\n## Certificaciones\nNo especificado\n";

        $response = $this->postJson('/api/profile/ats/confirm', [
            'version_optimizada_md' => $markdown,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('slug', 'ats-optimizado');
        $response->assertJsonPath('headline', 'Jane Doe');
        $response->assertJsonPath('summary', 'Resumen optimizado.');
        $response->assertJsonPath('is_active', false);

        $this->assertSame('Original headline', $default->fresh()->headline);
    }
}

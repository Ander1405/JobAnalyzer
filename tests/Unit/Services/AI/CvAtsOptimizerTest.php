<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use App\DTOs\AiUsage;
use App\Models\Profile;
use App\Services\AI\AIProviderFactory;
use App\Services\AI\CvAtsOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class CvAtsOptimizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_scores_a_profile_and_returns_an_optimized_markdown_version(): void
    {
        config(['jobhunter.claude_cli.binary' => base_path('tests/Fixtures/fake-claude-cli-ats')]);

        $profile = Profile::factory()->active()->create();

        $result = (new CvAtsOptimizer(new AIProviderFactory))->analyze($profile);

        $this->assertSame(72, $result->atsScore);
        $this->assertCount(1, $result->problemas);
        $this->assertSame(['CI/CD', 'AWS'], $result->keywordsFaltantes);
        $this->assertStringContainsString('# Jane Doe', $result->versionOptimizadaMd);
        $this->assertSame(1800, $result->usage->durationMs);
    }

    public function test_it_throws_when_the_ai_call_fails(): void
    {
        config(['jobhunter.claude_cli.binary' => base_path('tests/Fixtures/fake-claude-cli-failing')]);

        $profile = Profile::factory()->active()->create();

        $this->expectException(RuntimeException::class);

        (new CvAtsOptimizer(new AIProviderFactory))->analyze($profile);
    }

    public function test_parse_response_rejects_an_out_of_range_ats_score(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ats_score');

        (new CvAtsOptimizer(new AIProviderFactory))->parseResponse(json_encode([
            'ats_score' => 150,
            'problemas' => [],
            'keywords_faltantes' => [],
            'recomendaciones_formato' => [],
            'version_optimizada_md' => '# CV',
        ]), new AiUsage(0), 'test-model');
    }

    public function test_parse_response_rejects_an_empty_optimized_markdown(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('version_optimizada_md');

        (new CvAtsOptimizer(new AIProviderFactory))->parseResponse(json_encode([
            'ats_score' => 80,
            'problemas' => [],
            'keywords_faltantes' => [],
            'recomendaciones_formato' => [],
            'version_optimizada_md' => '   ',
        ]), new AiUsage(0), 'test-model');
    }

    public function test_parse_response_accepts_a_valid_payload(): void
    {
        $result = (new CvAtsOptimizer(new AIProviderFactory))->parseResponse(json_encode([
            'ats_score' => 65,
            'problemas' => ['Falta cuantificar logros.'],
            'keywords_faltantes' => ['AWS'],
            'recomendaciones_formato' => ['Usa una sola columna.'],
            'version_optimizada_md' => '# CV optimizado',
        ]), new AiUsage(400, 5, 5, 0.001), 'test-model');

        $this->assertSame(65, $result->atsScore);
        $this->assertSame(['AWS'], $result->keywordsFaltantes);
        $this->assertSame('test-model', $result->model);
    }
}

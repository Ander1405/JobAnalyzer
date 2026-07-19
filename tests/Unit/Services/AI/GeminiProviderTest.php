<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use App\Models\Job;
use App\Services\AI\GeminiProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiProviderTest extends TestCase
{
    public function test_it_extracts_and_parses_the_candidate_text(): void
    {
        config([
            'jobhunter.gemini.api_key' => 'test-key',
            'jobhunter.gemini.model' => 'gemini-flash-latest',
        ]);

        $payload = [
            'match_score' => 65,
            'diagnostico' => 'Encaje parcial.',
            'tips_postulacion' => ['Tip 1'],
            'tailoring_cv' => ['Ajuste 1'],
            'idioma' => 'Inglés',
            'tipo_contrato' => 'Freelance',
            'salario_normalizado' => 'No especificado',
            'moneda' => 'No especificado',
            'ingles_requerido' => 'Avanzado',
            'alerta_ingles' => true,
            'red_flags' => [],
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode($payload)]]]],
                ],
                'usageMetadata' => [
                    'promptTokenCount' => 200,
                    'candidatesTokenCount' => 90,
                ],
            ], 200),
        ]);

        $job = Job::factory()->make();

        $result = (new GeminiProvider)->analyze('# Perfil de prueba', $job);

        $this->assertSame(65, $result->analysis['match_score']);
        $this->assertSame('Inglés', $result->analysis['idioma']);

        $this->assertSame(200, $result->usage->inputTokens);
        $this->assertSame(90, $result->usage->outputTokens);
        $this->assertNull($result->usage->costUsd);
        $this->assertSame('gemini-flash-latest', $result->model);
    }
}

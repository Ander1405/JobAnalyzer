<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use App\Models\Job;
use App\Services\AI\OpenRouterProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenRouterProviderTest extends TestCase
{
    public function test_it_extracts_and_parses_the_message_content(): void
    {
        config([
            'jobhunter.openrouter.api_key' => 'test-key',
            'jobhunter.openrouter.model' => 'some-model:free',
        ]);

        $payload = [
            'match_score' => 40,
            'diagnostico' => 'Encaje bajo.',
            'tips_postulacion' => ['Tip 1'],
            'tailoring_cv' => ['Ajuste 1'],
            'idioma' => 'Ambos',
            'tipo_contrato' => 'Término fijo',
            'salario_normalizado' => 'No especificado',
            'moneda' => 'COP',
            'ingles_requerido' => 'No especificado',
            'alerta_ingles' => false,
            'red_flags' => ['Descripción vaga.'],
        ];

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode($payload)]],
                ],
                'usage' => [
                    'prompt_tokens' => 150,
                    'completion_tokens' => 75,
                    'cost' => 0.0003,
                ],
            ], 200),
        ]);

        $job = Job::factory()->make();

        $result = (new OpenRouterProvider)->analyze('# Perfil de prueba', $job);

        $this->assertSame(40, $result->analysis['match_score']);
        $this->assertSame('COP', $result->analysis['moneda']);

        $this->assertSame(150, $result->usage->inputTokens);
        $this->assertSame(75, $result->usage->outputTokens);
        $this->assertSame(0.0003, $result->usage->costUsd);
        $this->assertSame('some-model:free', $result->model);
    }
}

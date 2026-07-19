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
        ];

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode($payload)]],
                ],
            ], 200),
        ]);

        $job = Job::factory()->make();

        $result = (new OpenRouterProvider)->analyze('# Perfil de prueba', $job);

        $this->assertSame(40, $result['match_score']);
        $this->assertSame('COP', $result['moneda']);
    }
}

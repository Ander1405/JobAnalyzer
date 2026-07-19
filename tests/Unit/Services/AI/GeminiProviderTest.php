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
            'jobhunter.gemini.model' => 'gemini-2.0-flash',
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
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode($payload)]]]],
                ],
            ], 200),
        ]);

        $job = Job::factory()->make();

        $result = (new GeminiProvider)->analyze('# Perfil de prueba', $job);

        $this->assertSame(65, $result['match_score']);
        $this->assertSame('Inglés', $result['idioma']);
    }
}

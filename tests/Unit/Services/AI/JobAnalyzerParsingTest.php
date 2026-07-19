<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use App\Services\AI\JobAnalyzer;
use RuntimeException;
use Tests\TestCase;

class JobAnalyzerParsingTest extends TestCase
{
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'match_score' => 72,
            'diagnostico' => 'Buen encaje con el perfil.',
            'tips_postulacion' => ['Resalta tu experiencia con Laravel.'],
            'tailoring_cv' => ['Agrega métricas concretas.'],
            'idioma' => 'Español',
            'tipo_contrato' => 'Indefinido',
            'salario_normalizado' => 'No especificado',
            'moneda' => 'No especificado',
        ], $overrides);
    }

    public function test_it_parses_a_valid_json_response(): void
    {
        $result = JobAnalyzer::parseAiResponse(json_encode($this->validPayload()));

        $this->assertSame(72, $result['match_score']);
        $this->assertSame('Buen encaje con el perfil.', $result['diagnostico']);
    }

    public function test_it_strips_markdown_fences_before_parsing(): void
    {
        $fenced = "```json\n".json_encode($this->validPayload())."\n```";

        $result = JobAnalyzer::parseAiResponse($fenced);

        $this->assertSame(72, $result['match_score']);
    }

    public function test_it_clamps_an_out_of_range_match_score(): void
    {
        $result = JobAnalyzer::parseAiResponse(json_encode($this->validPayload(['match_score' => 150])));

        $this->assertSame(100, $result['match_score']);
    }

    public function test_it_throws_on_malformed_json(): void
    {
        $this->expectException(RuntimeException::class);

        JobAnalyzer::parseAiResponse('this is not json');
    }

    public function test_it_throws_when_a_required_key_is_missing(): void
    {
        $payload = $this->validPayload();
        unset($payload['diagnostico']);

        $this->expectException(RuntimeException::class);

        JobAnalyzer::parseAiResponse(json_encode($payload));
    }
}

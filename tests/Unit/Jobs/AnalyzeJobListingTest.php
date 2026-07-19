<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\JobStatus;
use App\Jobs\AnalyzeJobListing;
use App\Models\AiSetting;
use App\Models\Job;
use App\Services\AI\JobAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnalyzeJobListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_delegates_to_the_job_analyzer_and_persists_the_result(): void
    {
        config(['jobhunter.gemini.api_key' => 'test-key']);
        AiSetting::current()->update(['provider' => 'gemini', 'model' => 'gemini-flash-latest']);

        $payload = [
            'match_score' => 92,
            'diagnostico' => 'Excelente encaje.',
            'tips_postulacion' => [],
            'tailoring_cv' => [],
            'idioma' => 'Español',
            'tipo_contrato' => 'Indefinido',
            'salario_normalizado' => 'No especificado',
            'moneda' => 'No especificado',
            'ingles_requerido' => 'No especificado',
            'alerta_ingles' => false,
            'red_flags' => [],
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode($payload)]]]],
                ],
                'usageMetadata' => ['promptTokenCount' => 10, 'candidatesTokenCount' => 5],
            ], 200),
        ]);

        $job = Job::factory()->create(['status' => JobStatus::Analyzing]);

        (new AnalyzeJobListing($job))->handle(app(JobAnalyzer::class));

        $fresh = $job->fresh();
        $this->assertSame(JobStatus::Analyzed, $fresh->status);
        $this->assertSame(92, $fresh->ai_analysis['match_score']);
    }
}

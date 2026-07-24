<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\JobStatus;
use App\Jobs\AnalyzeJobListing;
use App\Models\AiSetting;
use App\Models\Job;
use App\Models\Profile;
use App\Services\Jobs\JobAnalysisPipeline;
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
            'seniority_inferido' => 'No especificado',
            'modalidad_inferida' => 'No especificado',
            'skills_requeridos' => [],
            'resumen_ejecutivo' => 'Resumen de la vacante.',
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode($payload)]]]],
                ],
                'usageMetadata' => ['promptTokenCount' => 10, 'candidatesTokenCount' => 5],
            ], 200),
        ]);

        $job = Job::factory()->create([
            'status' => JobStatus::Analyzing,
            'title' => 'Desarrollador PHP',
            'description' => 'Laravel y Vue.',
        ]);

        (new AnalyzeJobListing($job))->handle(app(JobAnalysisPipeline::class));

        $fresh = $job->fresh();
        $this->assertSame(JobStatus::Analyzed, $fresh->status);
        $this->assertSame(92, $fresh->ai_analysis['match_score']);
    }

    public function test_it_discards_listings_the_profile_pre_filter_rejects_without_calling_the_ai(): void
    {
        Http::fake();

        // The pre-filter (ProfileMatcher) reads the active profile's raw_md for its
        // primary-skills check — without one, primarySkills is empty and it lets
        // everything through, which would make this test pass for the wrong reason.
        Profile::factory()->active()->create([
            'raw_md' => "# Perfil\n\nPHP, Laravel, Vue, JavaScript.",
        ]);

        $job = Job::factory()->create([
            'status' => JobStatus::Analyzing,
            'title' => 'Cocinero',
            'description' => 'Restaurante de comida rápida.',
        ]);

        (new AnalyzeJobListing($job))->handle(app(JobAnalysisPipeline::class));

        $this->assertSame(JobStatus::Discarded, $job->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_it_honours_an_explicit_request_to_skip_the_pre_filter(): void
    {
        config(['jobhunter.gemini.api_key' => 'test-key']);
        AiSetting::current()->update(['provider' => 'gemini', 'model' => 'gemini-flash-latest']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([], 500),
        ]);

        $job = Job::factory()->create([
            'status' => JobStatus::Analyzing,
            'title' => 'Cocinero',
            'description' => 'Restaurante de comida rápida.',
        ]);

        (new AnalyzeJobListing($job, skipPreFilter: true))->handle(app(JobAnalysisPipeline::class));

        // Reaching the provider at all is the point; the 500 is what proves it tried.
        $this->assertSame(JobStatus::Failed, $job->fresh()->status);
    }
}

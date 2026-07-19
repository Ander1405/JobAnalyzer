<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use App\Enums\JobStatus;
use App\Models\AiSetting;
use App\Models\Job;
use App\Models\Profile;
use App\Services\AI\AIProviderFactory;
use App\Services\AI\JobAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class JobAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    private string $profilePath;

    private string $originalProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->profilePath = storage_path('app/perfil.md');
        $this->originalProfile = file_get_contents($this->profilePath);

        config(['jobhunter.gemini.api_key' => 'test-key']);
        AiSetting::current()->update(['provider' => 'gemini', 'model' => 'gemini-flash-latest']);

        $payload = [
            'match_score' => 80,
            'diagnostico' => 'Buen encaje.',
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
    }

    protected function tearDown(): void
    {
        file_put_contents($this->profilePath, $this->originalProfile);

        parent::tearDown();
    }

    public function test_it_uses_the_active_profiles_raw_markdown_when_present(): void
    {
        Profile::factory()->active()->create(['raw_md' => '# Perfil de la base de datos, no del archivo']);

        $job = Job::factory()->create();

        (new JobAnalyzer(new AIProviderFactory))->analyze($job);

        Http::assertSent(function ($request) {
            return str_contains(json_encode($request->data()), 'Perfil de la base de datos, no del archivo');
        });
    }

    public function test_it_falls_back_to_perfilmd_file_when_there_is_no_active_profile(): void
    {
        file_put_contents($this->profilePath, '# Perfil de respaldo en el archivo');

        $job = Job::factory()->create();

        (new JobAnalyzer(new AIProviderFactory))->analyze($job);

        Http::assertSent(function ($request) {
            return str_contains(json_encode($request->data()), 'Perfil de respaldo en el archivo');
        });
    }

    public function test_it_marks_the_job_as_failed_instead_of_leaving_it_stuck_when_the_provider_cannot_be_built(): void
    {
        // Regression guard: analyze() runs inside a queued job (AnalyzeJobListing). An
        // exception thrown before the retryable AI call would otherwise propagate to the
        // queue worker's own failure handling and never update this model, leaving it
        // stuck at "analyzing" forever with no visible error.
        AiSetting::current()->update(['provider' => 'not_a_real_provider']);

        $job = Job::factory()->create();

        (new JobAnalyzer(new AIProviderFactory))->analyze($job);

        $fresh = $job->fresh();
        $this->assertSame(JobStatus::Failed, $fresh->status);
        $this->assertStringContainsString('not_a_real_provider', (string) $fresh->error_message);
    }
}

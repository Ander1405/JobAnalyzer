<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    protected $model = Job::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $source = fake()->randomElement(['jsearch', 'larajobs', 'infojobs']);
        $company = fake()->company();
        $title = fake()->jobTitle();
        $url = fake()->url();

        return [
            'hash' => hash('sha256', $source.$company.$title.$url),
            'source' => $source,
            'company' => $company,
            'title' => $title,
            'description' => fake()->paragraphs(3, true),
            'url' => $url,
            'contract_type' => fake()->randomElement(['FULL_TIME', 'CONTRACTOR', null]),
            'salary_raw' => null,
            'language' => null,
            'status' => JobStatus::Fetched,
            'application_status' => ApplicationStatus::New,
            'ai_provider' => null,
            'ai_analysis' => null,
            'notion_page_id' => null,
            'error_message' => null,
        ];
    }

    public function analyzed(): self
    {
        return $this->state(fn () => [
            'status' => JobStatus::Analyzed,
            'ai_provider' => 'claude_cli',
            'ai_analysis' => [
                'match_score' => 75,
                'diagnostico' => 'Buen encaje general con el perfil.',
                'tips_postulacion' => ['Resalta tu experiencia con Laravel.'],
                'tailoring_cv' => ['Agrega métricas concretas.'],
                'idioma' => 'Español',
                'tipo_contrato' => 'Indefinido',
                'salario_normalizado' => '4.000-6.000 USD/mes',
                'moneda' => 'USD',
            ],
        ]);
    }
}

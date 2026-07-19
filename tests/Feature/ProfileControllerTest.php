<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $profilePath;

    private string $originalProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->profilePath = storage_path('app/perfil.md');
        $this->originalProfile = file_get_contents($this->profilePath);
    }

    protected function tearDown(): void
    {
        file_put_contents($this->profilePath, $this->originalProfile);

        parent::tearDown();
    }

    public function test_it_returns_the_current_profile_content(): void
    {
        $response = $this->getJson('/api/profile');

        $response->assertOk()->assertJson(['content' => $this->originalProfile]);
    }

    public function test_it_uploads_and_converts_a_resume_pdf(): void
    {
        config(['jobhunter.claude_cli.binary' => base_path('tests/Fixtures/fake-claude-cli-with-preamble')]);

        $file = new UploadedFile(base_path('tests/Fixtures/sample-resume.pdf'), 'resume.pdf', 'application/pdf', null, true);

        $response = $this->post('/api/profile/upload', ['resume' => $file], ['Accept' => 'application/json']);

        $response->assertOk()->assertJson([
            'content' => "# Perfil profesional\n- Backend Developer, 3 años de experiencia.",
            'model' => 'default',
        ]);

        $this->assertSame(
            "# Perfil profesional\n- Backend Developer, 3 años de experiencia.",
            file_get_contents($this->profilePath),
        );
    }

    public function test_it_rejects_a_non_pdf_upload(): void
    {
        $file = UploadedFile::fake()->create('resume.txt', 10);

        $response = $this->post('/api/profile/upload', ['resume' => $file], ['Accept' => 'application/json']);

        $response->assertUnprocessable()->assertJsonValidationErrors('resume');
    }
}

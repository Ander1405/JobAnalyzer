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

    public function test_it_imports_and_structures_a_resume_pdf_without_calling_ai(): void
    {
        $file = new UploadedFile(base_path('tests/Fixtures/sample-resume-full.pdf'), 'resume.pdf', 'application/pdf', null, true);

        $response = $this->post('/api/profile/import', ['cv' => $file], ['Accept' => 'application/json']);

        $response->assertOk();
        $response->assertJsonPath('profile.slug', 'default');
        $response->assertJsonPath('profile.contact.email', 'jane.doe@example.com');
        $response->assertJsonPath('profile.languages.english_level', 'B2');
        $response->assertJsonPath('profile.is_active', true);

        $this->assertDatabaseHas('profiles', ['slug' => 'default', 'is_active' => true]);
        $this->assertStringContainsString('jane.doe@example.com', file_get_contents($this->profilePath));
    }

    public function test_it_imports_a_plain_text_resume(): void
    {
        $file = new UploadedFile(base_path('tests/Fixtures/sample-resume.txt'), 'resume.txt', 'text/plain', null, true);

        $response = $this->post('/api/profile/import', ['cv' => $file], ['Accept' => 'application/json']);

        $response->assertOk();
        $response->assertJsonPath('profile.contact.name', 'Jane Doe');
    }

    public function test_it_rejects_an_unsupported_file_type(): void
    {
        $file = UploadedFile::fake()->create('resume.docx', 10);

        $response = $this->post('/api/profile/import', ['cv' => $file], ['Accept' => 'application/json']);

        $response->assertUnprocessable()->assertJsonValidationErrors('cv');
    }

    public function test_it_rejects_a_scanned_pdf_with_no_extractable_text(): void
    {
        $file = new UploadedFile(base_path('tests/Fixtures/sample-resume-scanned.pdf'), 'scanned.pdf', 'application/pdf', null, true);

        $response = $this->post('/api/profile/import', ['cv' => $file], ['Accept' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message']);
        $this->assertDatabaseMissing('profiles', ['slug' => 'default']);
    }
}

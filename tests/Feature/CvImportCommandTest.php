<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CvImportCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $profilePath;

    private ?string $originalProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->profilePath = storage_path("app/perfil_{$this->actingUser->id}.md");
        $this->originalProfile = file_exists($this->profilePath) ? file_get_contents($this->profilePath) : null;
    }

    protected function tearDown(): void
    {
        if ($this->originalProfile === null) {
            @unlink($this->profilePath);
        } else {
            file_put_contents($this->profilePath, $this->originalProfile);
        }

        parent::tearDown();
    }

    public function test_it_imports_a_cv_file_into_the_default_profile(): void
    {
        $this->artisan('cv:import', [
            'path' => base_path('tests/Fixtures/sample-resume.txt'),
            '--user' => $this->actingUser->email,
        ])->assertSuccessful();

        $profile = Profile::where('slug', 'default')->first();

        $this->assertNotNull($profile);
        $this->assertTrue($profile->is_active);
        $this->assertSame('jane.doe@example.com', $profile->contact['email']);
        $this->assertSame('B2', $profile->languages['english_level']);
    }

    public function test_it_creates_a_named_variant_via_the_slug_option(): void
    {
        $this->artisan('cv:import', [
            'path' => base_path('tests/Fixtures/sample-resume.txt'),
            '--slug' => 'backend',
            '--user' => $this->actingUser->email,
        ])->assertSuccessful();

        $this->assertDatabaseHas('profiles', ['slug' => 'backend', 'is_active' => true]);
    }

    public function test_it_fails_gracefully_for_a_missing_file(): void
    {
        $this->artisan('cv:import', ['path' => '/nonexistent/cv.pdf', '--user' => $this->actingUser->email])
            ->assertFailed();
    }
}

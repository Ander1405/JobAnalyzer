<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSyncCommandTest extends TestCase
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

    public function test_it_syncs_the_active_profile_from_the_edited_file(): void
    {
        Profile::factory()->active()->create(['slug' => 'default']);

        file_put_contents($this->profilePath, "# Manually edited\n\n## Skills\n- Go\n- Kubernetes\n");

        $this->artisan('profile:sync')->assertSuccessful();

        $profile = Profile::where('slug', 'default')->first();
        $this->assertSame(['Go', 'Kubernetes'], $profile->skills);
    }

    public function test_it_fails_gracefully_without_an_active_profile(): void
    {
        $this->artisan('profile:sync')->assertFailed();
    }
}

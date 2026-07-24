<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileVariantControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $profilePath;

    private string $originalProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->profilePath = storage_path("app/perfil_{$this->actingUser->id}.md");
        $this->originalProfile = file_exists($this->profilePath) ? file_get_contents($this->profilePath) : '';
    }

    protected function tearDown(): void
    {
        if ($this->originalProfile === '') {
            @unlink($this->profilePath);
        } else {
            file_put_contents($this->profilePath, $this->originalProfile);
        }

        parent::tearDown();
    }

    public function test_it_lists_all_profiles(): void
    {
        Profile::factory()->active()->create(['slug' => 'default']);
        Profile::factory()->create(['slug' => 'backend']);

        $response = $this->getJson('/api/profiles');

        $response->assertOk()->assertJsonCount(2);
    }

    public function test_it_creates_a_variant_from_default(): void
    {
        Profile::factory()->active()->create(['slug' => 'default', 'skills' => ['PHP', 'Laravel']]);

        $response = $this->postJson('/api/profiles', [
            'slug' => 'backend',
            'label' => 'Backend focus',
            'skills' => ['PHP'],
        ]);

        $response->assertCreated()->assertJsonPath('slug', 'backend');
        $this->assertDatabaseHas('profiles', ['slug' => 'backend', 'is_active' => false]);
    }

    public function test_it_activates_a_variant(): void
    {
        Profile::factory()->active()->create(['slug' => 'default']);
        $variant = Profile::factory()->create(['slug' => 'backend', 'raw_md' => "# Backend\n"]);

        $response = $this->postJson("/api/profile/{$variant->slug}/activate");

        $response->assertOk()->assertJsonPath('is_active', true);
        $this->assertDatabaseHas('profiles', ['slug' => 'default', 'is_active' => false]);
        $this->assertSame("# Backend\n", file_get_contents($this->profilePath));
    }

    public function test_it_syncs_only_the_active_profile(): void
    {
        Profile::factory()->create(['slug' => 'default', 'is_active' => false]);

        $response = $this->postJson('/api/profile/default/sync');

        $response->assertStatus(409);
    }

    public function test_it_updates_a_profile_and_regenerates_its_markdown(): void
    {
        $profile = Profile::factory()->active()->create(['slug' => 'default']);

        $response = $this->putJson("/api/profile/{$profile->slug}", [
            'skills' => ['PHP', 'Laravel', 'Docker'],
        ]);

        $response->assertOk()->assertJsonPath('skills', ['PHP', 'Laravel', 'Docker']);
        $this->assertStringContainsString('Docker', file_get_contents($this->profilePath));
    }

    public function test_it_syncs_from_edited_content_sent_by_the_browser_editor(): void
    {
        Profile::factory()->active()->create(['slug' => 'default']);

        $response = $this->postJson('/api/profile/default/sync', [
            'content' => "# Editado en el navegador\n\n## Skills\n- Rust\n",
        ]);

        $response->assertOk()->assertJsonPath('skills', ['Rust']);
        $this->assertStringContainsString('Rust', file_get_contents($this->profilePath));
    }
}

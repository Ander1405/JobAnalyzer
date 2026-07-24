<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $profilePath;

    private string $originalProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->profilePath = storage_path("app/perfil_{$this->actingUser->id}.md");
        $this->originalProfile = file_exists($this->profilePath) ? file_get_contents($this->profilePath) : '';

        config(['jobhunter.claude_cli.binary' => base_path('tests/Fixtures/fake-claude-cli-profile-review')]);
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

    public function test_it_returns_suggestions_and_usage_for_a_reviewable_profile(): void
    {
        Profile::factory()->active()->create([
            'slug' => 'default',
            'source_text' => "Jane Doe\nDesarrolladora backend con experiencia en Docker.",
        ]);

        $response = $this->postJson('/api/profile/default/review');

        $response->assertOk();
        $response->assertJsonCount(2, 'suggestions');
        $response->assertJsonPath('suggestions.0.category', 'correction');
        $response->assertJsonPath('usage.durationMs', 1500);
    }

    public function test_it_rejects_review_when_the_profile_has_no_stored_source_text(): void
    {
        Profile::factory()->active()->withoutSourceText()->create(['slug' => 'default']);

        $response = $this->postJson('/api/profile/default/review');

        $response->assertStatus(422);
        $response->assertJsonStructure(['message']);
    }

    public function test_it_applies_approved_suggestions_and_updates_the_profile(): void
    {
        $profile = Profile::factory()->active()->create([
            'slug' => 'default',
            'skills' => ['PHP', 'Laravel'],
        ]);

        $response = $this->postJson("/api/profile/{$profile->slug}/suggestions/apply", [
            'suggestions' => [
                ['field' => 'skills', 'action' => 'add', 'index' => null, 'suggested' => 'Docker'],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('skills', ['PHP', 'Laravel', 'Docker']);
        $this->assertStringContainsString('Docker', file_get_contents($this->profilePath));
    }

    public function test_it_rejects_a_malformed_apply_payload(): void
    {
        $profile = Profile::factory()->active()->create(['slug' => 'default']);

        $response = $this->postJson("/api/profile/{$profile->slug}/suggestions/apply", [
            'suggestions' => [
                ['field' => 'not-a-real-field', 'action' => 'add', 'suggested' => 'x'],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_it_rejects_a_non_remove_suggestion_missing_a_suggested_value(): void
    {
        $profile = Profile::factory()->active()->create(['slug' => 'default']);

        $response = $this->postJson("/api/profile/{$profile->slug}/suggestions/apply", [
            'suggestions' => [
                ['field' => 'headline', 'action' => 'replace', 'index' => null, 'suggested' => null],
            ],
        ]);

        $response->assertStatus(422);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Profile;

use App\Models\Profile;
use App\Services\Profile\EnglishLevelDetector;
use App\Services\Profile\ProfileBuilder;
use App\Services\Profile\ProfileVariantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ProfileVariantServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $profilePath;

    private string $originalProfile;

    private ProfileVariantService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->profilePath = storage_path('app/perfil.md');
        $this->originalProfile = file_get_contents($this->profilePath);
        $this->service = new ProfileVariantService(new ProfileBuilder(new EnglishLevelDetector));
    }

    protected function tearDown(): void
    {
        file_put_contents($this->profilePath, $this->originalProfile);

        parent::tearDown();
    }

    private function makeDefaultProfile(): Profile
    {
        return Profile::factory()->active()->create([
            'slug' => 'default',
            'skills' => ['PHP', 'Laravel', 'Vue.js'],
            'experience' => ['Backend Developer en Acme Corp.'],
        ]);
    }

    public function test_it_creates_a_variant_reusing_default_content_only(): void
    {
        $this->makeDefaultProfile();

        $variant = $this->service->createVariant('backend', 'Backend focus', [
            'skills' => ['PHP', 'Laravel'],
        ]);

        $this->assertSame('backend', $variant->slug);
        $this->assertSame(['PHP', 'Laravel'], $variant->skills);
        $this->assertSame(['Backend Developer en Acme Corp.'], $variant->experience);
        $this->assertFalse($variant->is_active);
    }

    public function test_it_refuses_to_create_a_variant_without_a_default_profile(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->createVariant('backend', 'Backend focus');
    }

    public function test_activating_a_variant_deactivates_others_and_rewrites_perfil_md(): void
    {
        $default = $this->makeDefaultProfile();
        $variant = $this->service->createVariant('backend', 'Backend focus');

        $activated = $this->service->activate($variant);

        $this->assertTrue($activated->is_active);
        $this->assertFalse($default->fresh()->is_active);
        $this->assertSame($variant->raw_md, file_get_contents($this->profilePath));
    }

    public function test_it_syncs_the_active_profile_from_a_hand_edited_file(): void
    {
        $this->makeDefaultProfile();

        file_put_contents($this->profilePath, "# Editor\n\n## Skills\n- PHP\n- Docker\n");

        $synced = $this->service->syncActiveFromFile();

        $this->assertSame(['PHP', 'Docker'], $synced->skills);
        $this->assertSame('Editor', $synced->headline);
    }

    public function test_it_refuses_to_sync_without_an_active_profile(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->syncActiveFromFile();
    }
}

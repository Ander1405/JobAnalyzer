<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Profile;

use App\Models\AiSetting;
use App\Services\AI\AIProviderFactory;
use App\Services\Profile\ProfileConverter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileConverterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_converts_raw_resume_text_via_the_configured_provider(): void
    {
        config(['jobhunter.claude_cli.binary' => base_path('tests/Fixtures/fake-claude-cli')]);
        AiSetting::current()->update(['provider' => 'claude_cli', 'model' => null]);

        $result = (new ProfileConverter(new AIProviderFactory))->convert('Jane Doe, Laravel Developer, 5 years experience.');

        $this->assertNotEmpty($result->text);
        $this->assertSame('default', $result->model);
        $this->assertSame(2997, $result->usage->durationMs);
    }

    public function test_it_strips_preamble_text_and_markdown_fences(): void
    {
        config(['jobhunter.claude_cli.binary' => base_path('tests/Fixtures/fake-claude-cli-with-preamble')]);
        AiSetting::current()->update(['provider' => 'claude_cli', 'model' => null]);

        $result = (new ProfileConverter(new AIProviderFactory))->convert('some resume text');

        $this->assertSame("# Perfil profesional\n- Backend Developer, 3 años de experiencia.", $result->text);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use App\DTOs\AiUsage;
use App\Models\Job;
use App\Models\Profile;
use App\Services\AI\AIProviderFactory;
use App\Services\AI\CvTailorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class CvTailorerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_tailors_the_profile_for_a_specific_job(): void
    {
        config(['jobhunter.claude_cli.binary' => base_path('tests/Fixtures/fake-claude-cli-tailor')]);

        $profile = Profile::factory()->active()->create([
            'headline' => 'Desarrollador backend',
            'experience' => ['Backend developer en Acme Corp.'],
            'skills' => ['PHP', 'Laravel'],
        ]);
        $job = Job::factory()->create(['title' => 'Backend Engineer', 'company' => 'Acme']);

        $result = (new CvTailorer(new AIProviderFactory))->tailor($profile, $job, ['Destacar experiencia con APIs']);

        $this->assertSame('Desarrollador backend Laravel orientado a APIs', $result->headline);
        $this->assertSame(['Laravel', 'PHP', 'Docker'], $result->skills);
        $this->assertSame(1200, $result->usage->durationMs);
    }

    public function test_it_throws_when_the_ai_call_fails(): void
    {
        config(['jobhunter.claude_cli.binary' => base_path('tests/Fixtures/fake-claude-cli-failing')]);

        $profile = Profile::factory()->active()->create();
        $job = Job::factory()->create();

        $this->expectException(RuntimeException::class);

        (new CvTailorer(new AIProviderFactory))->tailor($profile, $job, ['x']);
    }

    public function test_parse_response_rejects_a_non_object_payload(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JSON object');

        (new CvTailorer(new AIProviderFactory))->parseResponse('"just a string"', new AiUsage(0), 'test-model');
    }

    public function test_parse_response_rejects_a_missing_headline(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('headline');

        (new CvTailorer(new AIProviderFactory))->parseResponse(json_encode([
            'summary' => 'x',
            'experience' => [],
            'skills' => [],
        ]), new AiUsage(0), 'test-model');
    }

    public function test_parse_response_rejects_a_non_string_array_item(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('skills');

        (new CvTailorer(new AIProviderFactory))->parseResponse(json_encode([
            'headline' => 'x',
            'summary' => 'x',
            'experience' => [],
            'skills' => [42],
        ]), new AiUsage(0), 'test-model');
    }

    public function test_parse_response_accepts_a_valid_payload(): void
    {
        $result = (new CvTailorer(new AIProviderFactory))->parseResponse(json_encode([
            'headline' => 'Backend dev',
            'summary' => 'Summary',
            'experience' => ['Line 1'],
            'skills' => ['PHP'],
        ]), new AiUsage(500, 10, 20, 0.01), 'test-model');

        $this->assertSame('Backend dev', $result->headline);
        $this->assertSame(['Line 1'], $result->experience);
        $this->assertSame('test-model', $result->model);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use App\Models\Job;
use App\Services\AI\ClaudeCliProvider;
use RuntimeException;
use Tests\TestCase;

class ClaudeCliProviderTest extends TestCase
{
    public function test_it_extracts_and_parses_the_result_field_from_the_cli_json_output(): void
    {
        config(['jobhunter.claude_cli.binary' => base_path('tests/Fixtures/fake-claude-cli')]);

        $job = Job::factory()->make(['title' => 'Backend Developer', 'company' => 'Acme']);

        $result = (new ClaudeCliProvider)->analyze('# Perfil de prueba', $job);

        $this->assertSame(88, $result->analysis['match_score']);
        $this->assertSame('Español', $result->analysis['idioma']);

        $this->assertSame(2997, $result->usage->durationMs);
        $this->assertSame(120, $result->usage->inputTokens);
        $this->assertSame(340, $result->usage->outputTokens);
        $this->assertSame(0.0512, $result->usage->costUsd);
    }

    public function test_it_throws_a_clear_exception_when_the_binary_exits_with_an_error(): void
    {
        config(['jobhunter.claude_cli.binary' => base_path('tests/Fixtures/fake-claude-cli-failing')]);

        $job = Job::factory()->make();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/logged in/');

        (new ClaudeCliProvider)->analyze('# Perfil de prueba', $job);
    }
}

<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTOs\AiAnalysisResult;
use App\DTOs\AiCompletionResult;
use App\DTOs\AiUsage;
use App\Models\Job;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class ClaudeCliProvider implements AIProvider
{
    public function __construct(private readonly ?string $model = null) {}

    public function analyze(string $perfilMd, Job $job): AiAnalysisResult
    {
        $completion = $this->complete(JobAnalyzer::systemPrompt(), JobAnalyzer::userPrompt($perfilMd, $job));

        return new AiAnalysisResult(
            JobAnalyzer::parseAiResponse($completion->text),
            $completion->usage,
            $completion->model,
        );
    }

    public function complete(string $systemPrompt, string $userPrompt): AiCompletionResult
    {
        $binary = (string) config('jobhunter.claude_cli.binary');
        $model = $this->model ?? config('jobhunter.claude_cli.model');

        $command = [$binary, '-p', '--output-format', 'json'];

        if (! empty($model)) {
            $command[] = '--model';
            $command[] = (string) $model;
        }

        $process = new Process($command);
        $process->setInput($systemPrompt."\n\n".$userPrompt);
        $process->setTimeout(120);

        if ($home = $this->resolveHome()) {
            $process->setEnv(['HOME' => $home]);
        }

        $startedAt = microtime(true);

        try {
            $process->run();
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Unable to run the Claude CLI binary [{$binary}]. Verify it is installed and in your PATH: {$exception->getMessage()}",
                previous: $exception,
            );
        }

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                "Claude CLI exited with code {$process->getExitCode()}. Verify you are logged in (run `claude` in your terminal). ".
                "Stdout: {$process->getOutput()} Stderr: {$process->getErrorOutput()}"
            );
        }

        $decoded = json_decode($process->getOutput(), true);

        if (! is_array($decoded) || ! isset($decoded['result']) || ! is_string($decoded['result'])) {
            throw new RuntimeException('Unexpected Claude CLI output format: missing "result" field.');
        }

        $usage = new AiUsage(
            durationMs: (int) ($decoded['duration_ms'] ?? round((microtime(true) - $startedAt) * 1000)),
            inputTokens: $decoded['usage']['input_tokens'] ?? null,
            outputTokens: $decoded['usage']['output_tokens'] ?? null,
            costUsd: isset($decoded['total_cost_usd']) ? (float) $decoded['total_cost_usd'] : null,
        );

        return new AiCompletionResult($decoded['result'], $usage, (string) ($model ?: 'default'));
    }

    /**
     * PHP-FPM pools commonly run with `clear_env = yes` (Valet's default), which strips
     * HOME from the environment Process inherits — the Claude CLI then can't find its
     * login session and reports "Not logged in" even though the user is. `getenv()` is
     * empty in that case too, so fall back to reading the OS user database directly.
     */
    private function resolveHome(): ?string
    {
        $home = getenv('HOME');

        if (is_string($home) && $home !== '') {
            return $home;
        }

        if (function_exists('posix_getpwuid') && function_exists('posix_getuid')) {
            $info = posix_getpwuid(posix_getuid());

            return is_array($info) ? $info['dir'] : null;
        }

        return null;
    }
}

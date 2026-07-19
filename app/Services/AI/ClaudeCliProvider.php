<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Job;
use RuntimeException;
use Symfony\Component\Process\Process;

class ClaudeCliProvider implements AIProvider
{
    public function analyze(string $perfilMd, Job $job): array
    {
        $binary = (string) config('jobhunter.claude_cli.binary');
        $model = config('jobhunter.claude_cli.model');

        $command = [$binary, '-p', '--output-format', 'json'];

        if (! empty($model)) {
            $command[] = '--model';
            $command[] = (string) $model;
        }

        $process = new Process($command);
        $process->setInput(JobAnalyzer::buildPrompt($perfilMd, $job));
        $process->setTimeout(120);

        try {
            $process->run();
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                "Unable to run the Claude CLI binary [{$binary}]. Verify it is installed and in your PATH: {$exception->getMessage()}",
                previous: $exception,
            );
        }

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                "Claude CLI exited with code {$process->getExitCode()}. Verify you are logged in (run `claude` in your terminal). Output: {$process->getErrorOutput()}"
            );
        }

        $decoded = json_decode($process->getOutput(), true);

        if (! is_array($decoded) || ! isset($decoded['result']) || ! is_string($decoded['result'])) {
            throw new RuntimeException('Unexpected Claude CLI output format: missing "result" field.');
        }

        return JobAnalyzer::parseAiResponse($decoded['result']);
    }
}

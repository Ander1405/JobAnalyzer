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

        $prompt = $systemPrompt."\n\n".$userPrompt;
        $command = [$binary, '-p'];

        if (! empty($model)) {
            $command[] = '--model';
            $command[] = (string) $model;
        }

        $command[] = '--output-format';
        $command[] = 'json';
        $command[] = $prompt;

        // A model call outlives PHP-FPM's 30s max_execution_time, and the fatal it
        // raises kills the request mid-write instead of failing the analysis.
        set_time_limit(0);

        $process = new Process($command, env: $this->environment());
        $process->setTimeout(120);

        $startedAt = microtime(true);

        try {
            $process->run();
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Unable to run the Claude CLI binary [{$binary}]. Verify it is installed and in your PATH: {$exception->getMessage()}",
                previous: $exception,
            );
        }

        $decoded = json_decode($process->getOutput(), true);
        $decoded = is_array($decoded) ? $decoded : [];
        $cliMessage = is_string($decoded['result'] ?? null) ? $decoded['result'] : '';

        if (! $process->isSuccessful() || ($decoded['is_error'] ?? false) === true) {
            throw new RuntimeException($this->failureMessage($process->getExitCode(), $cliMessage, $process->getErrorOutput()));
        }

        if ($cliMessage === '') {
            throw new RuntimeException('Unexpected Claude CLI output format: missing "result" field.');
        }

        $usage = new AiUsage(
            durationMs: (int) ($decoded['duration_ms'] ?? round((microtime(true) - $startedAt) * 1000)),
            inputTokens: $decoded['usage']['input_tokens'] ?? null,
            outputTokens: $decoded['usage']['output_tokens'] ?? null,
            costUsd: isset($decoded['total_cost_usd']) ? (float) $decoded['total_cost_usd'] : null,
        );

        return new AiCompletionResult($cliMessage, $usage, (string) ($model ?: 'default'));
    }

    /**
     * The CLI only inherits what we hand it. HOME points it at ~/.claude, and the
     * token (when configured) is the only credential that survives outside the
     * user's GUI session — see the note in config/jobhunter.php.
     *
     * @return array<string, string>
     */
    private function environment(): array
    {
        $env = [];

        if ($home = $this->resolveHome()) {
            $env['HOME'] = $home;
        }

        $path = getenv('PATH');
        $env['PATH'] = is_string($path) && $path !== ''
            ? $path
            : '/usr/local/bin:/opt/homebrew/bin:/usr/bin:/bin:/usr/sbin:/sbin';

        if ($token = config('jobhunter.claude_cli.oauth_token')) {
            $env['CLAUDE_CODE_OAUTH_TOKEN'] = (string) $token;
        }

        if ($apiKey = config('jobhunter.claude_cli.api_key')) {
            $env['ANTHROPIC_API_KEY'] = (string) $apiKey;
        }

        $maxThinkingTokens = config('jobhunter.claude_cli.max_thinking_tokens');

        if ($maxThinkingTokens !== null) {
            $env['MAX_THINKING_TOKENS'] = (string) $maxThinkingTokens;
        }

        return $env;
    }

    /**
     * The raw CLI payload is a wall of JSON that buries the one line that matters,
     * so surface that line — and turn the auth failure into the actual remedy,
     * because "run `claude` in your terminal" does not fix a PHP-FPM request.
     */
    private function failureMessage(?int $exitCode, string $cliMessage, string $stderr): string
    {
        $detail = $cliMessage !== '' ? $cliMessage : trim($stderr);

        if (str_contains(strtolower($detail), 'not logged in') || str_contains($detail, '/login')) {
            return 'The Claude CLI has no usable session in this process. '
                .'On macOS the CLI session lives in the login Keychain, which PHP-FPM cannot read. '
                .'Run `claude setup-token` in your terminal and put the token in .env as CLAUDE_CODE_OAUTH_TOKEN '
                .'(then `php artisan config:clear`), or switch the AI provider to gemini/openrouter.';
        }

        return sprintf(
            'Claude CLI failed (exit code %s): %s',
            $exitCode ?? 'unknown',
            $detail !== '' ? $detail : 'no output',
        );
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

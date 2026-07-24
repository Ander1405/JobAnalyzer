<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use App\Services\AI\CvAtsOptimizer;
use App\Services\AI\CvTailorer;
use App\Services\AI\JobAnalyzer;
use App\Services\AI\ProfileReviewer;
use App\Services\AI\PromptCraft;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Locks the anti-generic contract: the shared PromptCraft blocks carry their
 * hard rules, and every AI process actually composes them into its system
 * prompt. Guards against a future refactor silently dropping the human-voice
 * wiring — the exact regression that made outputs read as AI-written.
 */
class PromptCraftTest extends TestCase
{
    public function test_human_voice_carries_its_hard_rules(): void
    {
        $block = PromptCraft::humanVoice();

        $this->assertStringContainsString('EVIDENCIA ANTES QUE ADJETIVOS', $block);
        $this->assertStringContainsString('ESPECIFICIDAD ACCIONABLE', $block);
        $this->assertStringContainsString('CERO MULETILLAS DE IA', $block);

        // A sample from the banned-phrase list in both languages must be present.
        foreach (['orientado a resultados', 'apasionado', 'spearhead', 'seamless', 'leverage'] as $banned) {
            $this->assertStringContainsString($banned, $block, "humanVoice() debe prohibir la muletilla [{$banned}].");
        }
    }

    public function test_authenticity_guard_forbids_fabrication(): void
    {
        $block = PromptCraft::authenticityGuard();

        $this->assertStringContainsString('NO INVENTAR', $block);
        $this->assertStringContainsString('PROHIBIDO', $block);
    }

    public function test_every_ai_process_composes_the_human_voice_block(): void
    {
        $voiceSentinel = 'CERO MULETILLAS DE IA';

        $this->assertStringContainsString($voiceSentinel, JobAnalyzer::systemPrompt());

        foreach ([CvAtsOptimizer::class, CvTailorer::class, ProfileReviewer::class] as $service) {
            $prompt = (new ReflectionMethod($service, 'systemPrompt'))->invoke(null);

            $this->assertStringContainsString(
                $voiceSentinel,
                $prompt,
                "{$service}::systemPrompt() debe componer la VOZ HUMANA de PromptCraft.",
            );
        }
    }

    public function test_rewrite_processes_also_compose_the_authenticity_guard(): void
    {
        $guardSentinel = 'NO INVENTAR';

        // The two processes that rewrite CV text must carry the anti-fabrication guard.
        foreach ([CvAtsOptimizer::class, CvTailorer::class] as $service) {
            $this->assertStringContainsString(
                $guardSentinel,
                (new ReflectionMethod($service, 'systemPrompt'))->invoke(null),
                "{$service}::systemPrompt() debe componer el guard de autenticidad.",
            );
        }
    }
}

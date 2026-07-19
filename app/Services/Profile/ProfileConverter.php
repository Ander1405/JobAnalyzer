<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\DTOs\AiCompletionResult;
use App\Services\AI\AIProviderFactory;

class ProfileConverter
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Eres un asistente que reestructura hojas de vida en un perfil profesional conciso en Markdown.

Recibirás el texto crudo extraído de una hoja de vida en PDF. Responde ÚNICAMENTE con el
perfil en Markdown, sin explicaciones adicionales, sin backticks ni bloques de código,
siguiendo exactamente esta estructura:

# Perfil profesional
- <rol principal>, <años de experiencia> en <empresa(s) más relevantes>, dominio de <industria/dominio>.
- Stack principal: <lenguajes, frameworks, bases de datos, herramientas>.
- Fortalezas: <2-4 logros o fortalezas concretas y medibles cuando sea posible>.
- Objetivo: <rol/nivel al que aspira, según lo que se infiera del CV>.
- Idiomas: <idiomas y nivel>.
- Modalidad preferida: <remoto/híbrido/presencial>. Ubicación: <ciudad, país>.

Reglas: sé específico y basado únicamente en lo que aparece en el texto. Si algún dato
(idiomas, modalidad, objetivo) no está explícito, infierelo razonablemente del contexto o
usa "No especificado". No inventes empresas, cifras ni tecnologías que no aparezcan.
PROMPT;

    public function __construct(private readonly AIProviderFactory $factory) {}

    public function convert(string $rawText): AiCompletionResult
    {
        $completion = $this->factory->make()->complete(self::SYSTEM_PROMPT, $rawText);

        return new AiCompletionResult(
            $this->sanitize($completion->text),
            $completion->usage,
            $completion->model,
        );
    }

    private function sanitize(string $text): string
    {
        $clean = trim($text);
        $clean = preg_replace('/^```(?:markdown|md)?\s*/i', '', $clean) ?? $clean;
        $clean = preg_replace('/\s*```$/', '', $clean) ?? $clean;

        $headingPosition = strpos($clean, '# ');

        if ($headingPosition !== false) {
            $clean = substr($clean, $headingPosition);
        }

        return trim($clean);
    }
}

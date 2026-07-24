<?php

declare(strict_types=1);

namespace App\Services\AI;

/**
 * Reusable prompt-engineering building blocks shared by every AI service.
 *
 * The four AI processes (job analysis + tips, ATS rewrite, CV tailoring, profile
 * review) all suffer the same failure mode: zero-shot single-pass prompts that
 * merely *ask* for specificity produce generic, template-sounding, easily
 * AI-detectable text. Instead of repeating the fix inline in each prompt, the
 * cross-cutting rules live here and each service composes the blocks it needs.
 *
 * This is the app-side equivalent of a "skills" library: deterministic,
 * versioned instruction modules a process opts into — no LLM router guessing
 * which skill applies, because each process already knows its own job.
 */
final class PromptCraft
{
    /**
     * Voice contract for any text that ends up in front of a recruiter (tips,
     * CV lines, summaries). Turns "be specific, not generic" from a wish into a
     * set of hard, checkable constraints: evidence before adjectives, concrete
     * specificity, uneven human phrasing, and a banned-phrase list of the words
     * that flag text as AI-written.
     */
    public static function humanVoice(): string
    {
        return <<<'BLOCK'
=== VOZ HUMANA (obligatorio en todo texto de cara al reclutador) ===
Escribe como una persona real que conoce ESTE caso concreto, no como un asistente
genérico. No basta con "ser específico": cumple estas reglas y auto-revísalas antes
de responder.

1. EVIDENCIA ANTES QUE ADJETIVOS. Cada afirmación se apoya en un hecho concreto que
   YA está en la fuente: una empresa, una tecnología, una cifra, un contexto de
   negocio. Prohibido un adjetivo de valor ("sólido", "amplio", "experto", "robusto")
   si no hay evidencia en el texto original que lo respalde.

2. ESPECIFICIDAD ACCIONABLE. Un buen enunciado combina: qué se hizo + en qué
   sistema o tecnología + para qué contexto de negocio + con qué resultado medible
   (solo si la fuente lo da).
     MAL:  "Amplia experiencia liderando equipos y entregando resultados."
     BIEN: "Lideré la migración de 42 endpoints de Laravel 9 a 11 en el equipo de
            pagos, sin downtime en producción."
   Si la fuente no trae una cifra, NO la inventes: usa el hecho cualitativo concreto
   que sí está (nombre del sistema, del cliente, del reto real).

3. FRASES DESPAREJAS. La IA produce viñetas uniformes; los humanos no. Mezcla frases
   cortas y directas con otras más largas. Varía el verbo con que arrancas. Nunca
   empieces dos viñetas seguidas con la misma estructura o el mismo verbo.

4. CERO MULETILLAS DE IA. Están PROHIBIDAS estas palabras y sus variantes, en español
   e inglés: apasionado, proactivo, orientado a resultados, orientado al detalle,
   sólidos conocimientos, amplia experiencia, altamente motivado, jugador de equipo,
   sinergia, aprovechar/leverage, potenciar, robusto, integral, holístico, en
   constante crecimiento/aprendizaje, buscar constantemente, dinámico, ecosistema,
   proven track record, results-driven, detail-oriented, spearhead, delve, foster,
   comprehensive, utilize, facilitate, cutting-edge, seamless, game-changer.
   Si ibas a usar una, bórrala y pon en su lugar el hecho concreto que intentaba
   resumir.

5. TONO. Directo y profesional, sin relleno de relaciones públicas. Verbo fuerte
   antes que adverbio; sustantivo concreto antes que categoría abstracta.
BLOCK;
    }

    /**
     * Anti-fabrication guard. Every rewrite/tailoring service must only reorder,
     * rephrase or surface facts already present in the source — and when the job
     * asks for something the profile lacks, flag the gap instead of inventing it.
     */
    public static function authenticityGuard(): string
    {
        return <<<'BLOCK'
=== NO INVENTAR (obligatorio) ===
Solo puedes reordenar, reformular o resaltar hechos que YA estén escritos en la
fuente. Está PROHIBIDO agregar experiencia, tecnologías, empresas, fechas, cifras o
logros que no aparezcan en el texto original. Si la vacante pide algo que la fuente
no tiene, NO lo rellenes: márcalo como hueco real en el campo de diagnóstico o
problemas, para que el candidato decida si lo cubre.
BLOCK;
    }
}

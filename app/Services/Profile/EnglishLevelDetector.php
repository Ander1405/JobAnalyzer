<?php

declare(strict_types=1);

namespace App\Services\Profile;

class EnglishLevelDetector
{
    /**
     * @var array<string, string>
     */
    private const KEYWORDS = [
        'nativo' => 'C2',
        'native' => 'C2',
        'bilingue' => 'C2',
        'bilingual' => 'C2',
        'fluido' => 'C1',
        'fluent' => 'C1',
        'avanzado' => 'C1',
        'advanced' => 'C1',
        'intermedio alto' => 'B2',
        'upper intermediate' => 'B2',
        'intermedio' => 'B1',
        'intermediate' => 'B1',
        'conversacional' => 'B1',
        'conversational' => 'B1',
        'basico' => 'A2',
        'basic' => 'A2',
        'elemental' => 'A2',
    ];

    /**
     * Deterministic (no AI) detection of the CEFR English level declared by the CV,
     * by scanning the languages section for a level code (B1, C2, ...) or a common
     * proficiency word near the word "inglés"/"english".
     *
     * @param  array<int, string>  $languageLines
     */
    public function detect(array $languageLines, string $fallbackText = ''): ?string
    {
        $haystack = $languageLines !== [] ? implode("\n", $languageLines) : $fallbackText;

        if ($haystack === '') {
            return null;
        }

        if (preg_match('/(?:ingl[eé]s|english)[^\n]{0,20}?\b(A1|A2|B1|B2|C1|C2)\b/iu', $haystack, $matches) === 1) {
            return strtoupper($matches[1]);
        }

        $normalized = $this->normalize($haystack);

        foreach (self::KEYWORDS as $keyword => $level) {
            if (preg_match('/(?:ingl[eé]s|english)[^\n]{0,20}?'.preg_quote($keyword, '/').'/iu', $normalized) === 1) {
                return $level;
            }
        }

        return null;
    }

    private function normalize(string $text): string
    {
        $translit = strtr($text, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N', 'Ü' => 'U',
        ]);

        return strtolower(trim($translit));
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Profile;

use RuntimeException;

class CvParser
{
    /**
     * @var array<string, array<int, string>>
     */
    private const SECTION_KEYWORDS = [
        'resumen' => ['resumen', 'perfil profesional', 'perfil', 'summary', 'profile', 'about me', 'objetivo', 'objective'],
        'experiencia' => ['experiencia', 'experiencia laboral', 'experience', 'work experience', 'professional experience'],
        'skills' => ['skills', 'habilidades', 'stack tecnico', 'tecnologias', 'technical skills'],
        'educacion' => ['educacion', 'education', 'formacion academica', 'formación académica'],
        'idiomas' => ['idiomas', 'languages'],
        'certificaciones' => ['certificaciones', 'certifications', 'certificados'],
    ];

    public function __construct(
        private readonly ResumeTextExtractor $extractor,
        private readonly EnglishLevelDetector $englishLevelDetector,
    ) {}

    /**
     * @return array{
     *     contact: array<string, string|null>,
     *     headline: string|null,
     *     summary: string|null,
     *     experience: array<int, string>,
     *     skills: array<int, string>,
     *     education: array<int, string>,
     *     languages: array{items: array<int, string>, english_level: string|null},
     *     certifications: array<int, string>,
     * }
     */
    public function parse(string $absolutePath, ?string $extension = null): array
    {
        $extension = strtolower($extension ?? (string) pathinfo($absolutePath, PATHINFO_EXTENSION));

        $text = match ($extension) {
            'pdf' => $this->extractor->extract($absolutePath),
            'txt', 'md' => trim((string) file_get_contents($absolutePath)),
            default => throw new RuntimeException("Unsupported CV file type [.{$extension}]. Upload a PDF, TXT or MD file."),
        };

        if (trim($text) === '') {
            throw new RuntimeException('The CV file contains no extractable text.');
        }

        return $this->structure($text);
    }

    /**
     * @return array{
     *     contact: array<string, string|null>,
     *     headline: string|null,
     *     summary: string|null,
     *     experience: array<int, string>,
     *     skills: array<int, string>,
     *     education: array<int, string>,
     *     languages: array{items: array<int, string>, english_level: string|null},
     *     certifications: array<int, string>,
     * }
     */
    private function structure(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];

        /** @var array<string, array<int, string>> $sections */
        $sections = [];
        $preamble = [];
        $currentSection = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            $keyword = $this->matchSectionKeyword($trimmed);

            if ($keyword !== null) {
                $currentSection = $keyword;
                $sections[$currentSection] ??= [];

                continue;
            }

            if ($currentSection === null) {
                $preamble[] = $trimmed;

                continue;
            }

            $sections[$currentSection][] = $this->stripBullet($trimmed);
        }

        $contact = $this->extractContact($text, $preamble);
        [$headline, $summaryFromPreamble] = $this->extractHeadlineAndSummary($preamble, $contact['name'] ?? null);

        $summary = isset($sections['resumen'])
            ? trim(implode("\n", $sections['resumen']))
            : $summaryFromPreamble;

        return [
            'contact' => $contact,
            'headline' => $headline,
            'summary' => $summary !== '' ? $summary : null,
            'experience' => $this->section($sections, 'experiencia'),
            'skills' => $this->splitSkills($this->section($sections, 'skills')),
            'education' => $this->section($sections, 'educacion'),
            'languages' => [
                'items' => $this->section($sections, 'idiomas'),
                'english_level' => $this->englishLevelDetector->detect($this->section($sections, 'idiomas'), $text),
            ],
            'certifications' => $this->section($sections, 'certificaciones'),
        ];
    }

    private function matchSectionKeyword(string $line): ?string
    {
        $normalized = $this->normalize(trim(ltrim($line, '#')));
        $normalized = rtrim($normalized, ':');

        if (mb_strlen($normalized) > 60) {
            return null;
        }

        foreach (self::SECTION_KEYWORDS as $section => $keywords) {
            foreach ($keywords as $keyword) {
                if ($normalized === $this->normalize($keyword)) {
                    return $section;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $preamble
     * @return array<string, string|null>
     */
    private function extractContact(string $text, array $preamble): array
    {
        $preambleText = implode("\n", $preamble);

        return [
            'name' => $this->extractName($preamble),
            'email' => $this->firstMatch('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $text),
            'phone' => $this->firstMatch('/(\+?\d[\d\s().\-]{6,}\d)/', $preambleText),
            'linkedin' => $this->firstMatch('#linkedin\.com/[\w\-/]+#i', $text),
            'github' => $this->firstMatch('#github\.com/[\w\-/]+#i', $text),
            'location' => $this->firstMatch('/(?:ubicaci[oó]n|location)\s*:\s*(.+)/i', $text, 1),
        ];
    }

    /**
     * @param  array<int, string>  $preamble
     */
    private function extractName(array $preamble): ?string
    {
        $first = $preamble[0] ?? null;

        if ($first === null) {
            return null;
        }

        if (mb_strlen($first) > 60 || str_contains($first, '@') || preg_match('/\d/', $first) === 1) {
            return null;
        }

        return $first;
    }

    /**
     * @param  array<int, string>  $preamble
     * @return array{0: string|null, 1: string}
     */
    private function extractHeadlineAndSummary(array $preamble, ?string $name): array
    {
        $rest = $name !== null ? array_slice($preamble, 1) : $preamble;
        $rest = array_values(array_filter($rest, fn (string $line) => ! $this->looksLikeContactLine($line)));

        $headline = $rest[0] ?? null;
        $headline = $headline !== null && mb_strlen($headline) <= 80 ? $headline : null;

        $leftover = $headline !== null ? array_slice($rest, 1) : $rest;

        return [$headline, trim(implode("\n", $leftover))];
    }

    private function looksLikeContactLine(string $line): bool
    {
        return (bool) preg_match('/@|linkedin\.com|github\.com|^\+?\d[\d\s().\-]{6,}\d$/i', $line);
    }

    /**
     * @param  array<string, array<int, string>>  $sections
     * @return array<int, string>
     */
    private function section(array $sections, string $key): array
    {
        return $sections[$key] ?? [];
    }

    /**
     * @param  array<int, string>  $lines
     * @return array<int, string>
     */
    private function splitSkills(array $lines): array
    {
        if (count($lines) === 1 && str_contains($lines[0], ',')) {
            return array_values(array_filter(array_map('trim', explode(',', $lines[0]))));
        }

        return $lines;
    }

    private function firstMatch(string $pattern, string $subject, int $group = 0): ?string
    {
        if (preg_match($pattern, $subject, $matches) !== 1) {
            return null;
        }

        return trim($matches[$group]);
    }

    private function stripBullet(string $line): string
    {
        return trim((string) preg_replace('/^[-•*]\s*|^\d+[.)]\s*/', '', $line));
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

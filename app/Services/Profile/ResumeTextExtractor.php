<?php

declare(strict_types=1);

namespace App\Services\Profile;

use RuntimeException;
use Smalot\PdfParser\Parser;
use Symfony\Component\Process\Process;
use Throwable;

class ResumeTextExtractor
{
    private const TRIM_CHARS = " \t\n\r\0\x0B\x0C";

    public function extract(string $absolutePath): string
    {
        try {
            $text = trim((new Parser)->parseFile($absolutePath)->getText(), self::TRIM_CHARS);
        } catch (Throwable) {
            $text = '';
        }

        if ($text === '') {
            $text = $this->extractWithPdftotext($absolutePath) ?? '';
        }

        if ($text === '') {
            throw new RuntimeException(
                'The PDF contains no extractable text. Upload a PDF with selectable text, or paste the CV as .txt/.md instead.',
            );
        }

        return $text;
    }

    private function extractWithPdftotext(string $absolutePath): ?string
    {
        $binary = (string) config('jobhunter.pdftotext_binary', 'pdftotext');
        $process = new Process([$binary, '-layout', $absolutePath, '-']);

        try {
            $process->run();
        } catch (Throwable) {
            return null;
        }

        if (! $process->isSuccessful()) {
            return null;
        }

        $text = trim($process->getOutput(), self::TRIM_CHARS);

        return $text !== '' ? $text : null;
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Profile;

use RuntimeException;
use Smalot\PdfParser\Parser;
use Throwable;

class ResumeTextExtractor
{
    public function extract(string $absolutePath): string
    {
        try {
            $text = trim((new Parser)->parseFile($absolutePath)->getText());
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Unable to parse PDF file: {$exception->getMessage()}",
                previous: $exception,
            );
        }

        if ($text === '') {
            throw new RuntimeException('The PDF contains no extractable text.');
        }

        return $text;
    }
}

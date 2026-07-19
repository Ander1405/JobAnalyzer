<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Profile;

use App\Services\Profile\ResumeTextExtractor;
use RuntimeException;
use Tests\TestCase;

class ResumeTextExtractorTest extends TestCase
{
    public function test_it_extracts_text_from_a_pdf(): void
    {
        $text = (new ResumeTextExtractor)->extract(base_path('tests/Fixtures/sample-resume.pdf'));

        $this->assertStringContainsString('Jane Doe', $text);
        $this->assertStringContainsString('Laravel Developer', $text);
    }

    public function test_it_throws_a_clear_exception_for_an_unparsable_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'not-a-pdf');
        file_put_contents($path, 'this is not a pdf');

        $this->expectException(RuntimeException::class);

        try {
            (new ResumeTextExtractor)->extract($path);
        } finally {
            unlink($path);
        }
    }

    public function test_it_sanitizes_invalid_utf8_bytes_from_the_pdftotext_fallback(): void
    {
        $fakeBinary = tempnam(sys_get_temp_dir(), 'fake-pdftotext');
        file_put_contents($fakeBinary, "#!/bin/sh\ncat <<'EOF'\nJane Doe \xFF\xFE Laravel Developer\nEOF\n");
        chmod($fakeBinary, 0755);

        config(['jobhunter.pdftotext_binary' => $fakeBinary]);

        $path = tempnam(sys_get_temp_dir(), 'not-a-pdf');
        file_put_contents($path, 'this is not a pdf');

        try {
            $text = (new ResumeTextExtractor)->extract($path);

            $this->assertTrue(mb_check_encoding($text, 'UTF-8'));
            $this->assertStringContainsString('Jane Doe', $text);
            $this->assertStringContainsString('Laravel Developer', $text);
        } finally {
            unlink($fakeBinary);
            unlink($path);
        }
    }
}

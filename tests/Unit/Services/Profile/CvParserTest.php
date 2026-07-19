<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Profile;

use App\Services\Profile\CvParser;
use App\Services\Profile\EnglishLevelDetector;
use App\Services\Profile\ResumeTextExtractor;
use RuntimeException;
use Tests\TestCase;

class CvParserTest extends TestCase
{
    private CvParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new CvParser(new ResumeTextExtractor, new EnglishLevelDetector);
    }

    public function test_it_extracts_contact_skills_and_english_level_from_a_pdf(): void
    {
        $profile = $this->parser->parse(base_path('tests/Fixtures/sample-resume-full.pdf'));

        $this->assertSame('Jane Doe', $profile['contact']['name']);
        $this->assertSame('jane.doe@example.com', $profile['contact']['email']);
        $this->assertSame('+1 555 123 4567', $profile['contact']['phone']);
        $this->assertSame('linkedin.com/in/janedoe', $profile['contact']['linkedin']);
        $this->assertContains('Laravel', $profile['skills']);
        $this->assertContains('MySQL', $profile['skills']);
        $this->assertSame('B2', $profile['languages']['english_level']);
        $this->assertNotEmpty($profile['experience']);
        $this->assertSame(['AWS Certified Developer'], $profile['certifications']);
        $this->assertStringContainsString('Jane Doe', $profile['source_text']);
    }

    public function test_it_structures_the_same_cv_from_plain_text(): void
    {
        $profile = $this->parser->parse(base_path('tests/Fixtures/sample-resume.txt'));

        $this->assertSame('Jane Doe', $profile['contact']['name']);
        $this->assertSame('jane.doe@example.com', $profile['contact']['email']);
        $this->assertContains('Laravel', $profile['skills']);
        $this->assertSame('B2', $profile['languages']['english_level']);
        $this->assertSame(
            ['Backend Developer en Acme Corp (2021-2024): lideró la migración a microservicios.', 'Junior Developer en Beta Inc (2019-2021): mantenimiento de APIs REST.'],
            $profile['experience'],
        );
        $this->assertStringContainsString('Jane Doe', $profile['source_text']);
    }

    public function test_it_throws_a_clear_error_for_a_scanned_pdf_without_calling_ai(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no extractable text');

        $this->parser->parse(base_path('tests/Fixtures/sample-resume-scanned.pdf'));
    }

    public function test_it_never_invents_content_for_missing_sections(): void
    {
        $path = sys_get_temp_dir().'/'.uniqid('minimal-cv-').'.txt';
        file_put_contents($path, "John Smith\nBackend Developer\n\nExperiencia\n- Something.\n");

        try {
            $profile = $this->parser->parse($path);
        } finally {
            unlink($path);
        }

        $this->assertSame([], $profile['skills']);
        $this->assertSame([], $profile['education']);
        $this->assertSame([], $profile['certifications']);
        $this->assertSame([], $profile['languages']['items']);
        $this->assertNull($profile['languages']['english_level']);
    }
}

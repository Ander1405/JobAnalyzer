<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Profile;

use App\Services\Profile\CvParser;
use App\Services\Profile\EnglishLevelDetector;
use App\Services\Profile\ProfileBuilder;
use App\Services\Profile\ResumeTextExtractor;
use Tests\TestCase;

class ProfileBuilderTest extends TestCase
{
    public function test_it_round_trips_structure_to_markdown_and_back(): void
    {
        $parser = new CvParser(new ResumeTextExtractor, new EnglishLevelDetector);
        $builder = new ProfileBuilder(new EnglishLevelDetector);

        $original = $parser->parse(base_path('tests/Fixtures/sample-resume.txt'));

        $markdown = $builder->toMarkdown($original);
        $roundTripped = $builder->fromMarkdown($markdown);

        $this->assertSame($original['headline'], $roundTripped['headline']);
        $this->assertSame($original['summary'], $roundTripped['summary']);
        $this->assertSame($original['experience'], $roundTripped['experience']);
        $this->assertSame($original['skills'], $roundTripped['skills']);
        $this->assertSame($original['education'], $roundTripped['education']);
        $this->assertSame($original['languages'], $roundTripped['languages']);
        $this->assertSame($original['certifications'], $roundTripped['certifications']);
        $this->assertSame($original['contact']['email'], $roundTripped['contact']['email']);
        $this->assertSame($original['contact']['name'], $roundTripped['contact']['name']);
    }

    public function test_it_renders_placeholders_for_missing_sections_without_inventing_content(): void
    {
        $builder = new ProfileBuilder(new EnglishLevelDetector);

        $markdown = $builder->toMarkdown([
            'contact' => ['name' => 'John Smith', 'email' => null, 'phone' => null, 'location' => null, 'linkedin' => null, 'github' => null],
            'headline' => null,
            'summary' => null,
            'experience' => [],
            'skills' => [],
            'education' => [],
            'languages' => ['items' => [], 'english_level' => null],
            'certifications' => [],
        ]);

        $this->assertStringContainsString('No especificado', $markdown);

        $parsed = $builder->fromMarkdown($markdown);

        $this->assertSame([], $parsed['skills']);
        $this->assertSame([], $parsed['certifications']);
        $this->assertNull($parsed['languages']['english_level']);
    }
}

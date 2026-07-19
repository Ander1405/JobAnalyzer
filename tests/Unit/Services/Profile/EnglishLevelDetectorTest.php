<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Profile;

use App\Services\Profile\EnglishLevelDetector;
use Tests\TestCase;

class EnglishLevelDetectorTest extends TestCase
{
    public function test_it_detects_a_cefr_code_next_to_english(): void
    {
        $detector = new EnglishLevelDetector;

        $this->assertSame('B2', $detector->detect(['Español nativo', 'Inglés B2']));
        $this->assertSame('C1', $detector->detect(['English: C1']));
    }

    public function test_it_detects_a_proficiency_word_when_no_cefr_code_is_present(): void
    {
        $detector = new EnglishLevelDetector;

        $this->assertSame('C1', $detector->detect(['Inglés avanzado']));
        $this->assertSame('B1', $detector->detect(['English: intermediate']));
        $this->assertSame('C2', $detector->detect(['Inglés nativo']));
    }

    public function test_it_returns_null_when_no_english_level_is_declared(): void
    {
        $detector = new EnglishLevelDetector;

        $this->assertNull($detector->detect(['Español nativo']));
        $this->assertNull($detector->detect([]));
    }
}

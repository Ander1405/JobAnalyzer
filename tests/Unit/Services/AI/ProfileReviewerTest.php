<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use App\Models\Profile;
use App\Services\AI\AIProviderFactory;
use App\Services\AI\ProfileReviewer;
use App\Services\Profile\EnglishLevelDetector;
use App\Services\Profile\ProfileBuilder;
use App\Services\Profile\ProfileVariantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ProfileReviewerTest extends TestCase
{
    use RefreshDatabase;

    private string $profilePath;

    private string $originalProfile;

    private ProfileReviewer $reviewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->profilePath = storage_path('app/perfil.md');
        $this->originalProfile = file_get_contents($this->profilePath);

        $this->reviewer = new ProfileReviewer(
            new AIProviderFactory,
            new ProfileVariantService(new ProfileBuilder(new EnglishLevelDetector)),
        );
    }

    protected function tearDown(): void
    {
        file_put_contents($this->profilePath, $this->originalProfile);

        parent::tearDown();
    }

    public function test_it_reviews_a_profile_and_returns_suggestions_with_usage(): void
    {
        config(['jobhunter.claude_cli.binary' => base_path('tests/Fixtures/fake-claude-cli-profile-review')]);

        $profile = Profile::factory()->active()->create([
            'source_text' => "Jane Doe\nDesarrolladora backend con experiencia en Docker.",
        ]);

        $result = $this->reviewer->review($profile);

        $this->assertCount(2, $result->suggestions);
        $this->assertSame('sugg-1', $result->suggestions[0]['id']);
        $this->assertSame('correction', $result->suggestions[0]['category']);
        $this->assertSame('skills', $result->suggestions[0]['field']);
        $this->assertSame('add', $result->suggestions[0]['action']);
        $this->assertSame('improvement', $result->suggestions[1]['category']);
        $this->assertSame(1500, $result->usage->durationMs);
        $this->assertSame(200, $result->usage->inputTokens);
    }

    public function test_it_refuses_to_review_a_profile_without_stored_source_text(): void
    {
        $profile = Profile::factory()->active()->withoutSourceText()->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Re-import the CV');

        $this->reviewer->review($profile);
    }

    public function test_parse_review_response_rejects_a_response_without_a_suggestions_array(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('suggestions');

        $this->reviewer->parseReviewResponse('{"foo": "bar"}');
    }

    public function test_parse_review_response_rejects_an_invalid_category(): void
    {
        $this->expectException(RuntimeException::class);

        $this->reviewer->parseReviewResponse(json_encode(['suggestions' => [
            ['category' => 'nonsense', 'field' => 'summary', 'action' => 'replace', 'suggested' => 'x', 'rationale' => 'y'],
        ]]));
    }

    public function test_parse_review_response_rejects_an_array_field_replace_without_an_index(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires an index');

        $this->reviewer->parseReviewResponse(json_encode(['suggestions' => [
            ['category' => 'correction', 'field' => 'skills', 'action' => 'replace', 'suggested' => 'Docker', 'rationale' => 'y'],
        ]]));
    }

    public function test_parse_review_response_rejects_a_scalar_field_with_an_index(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('scalar field');

        $this->reviewer->parseReviewResponse(json_encode(['suggestions' => [
            ['category' => 'correction', 'field' => 'headline', 'action' => 'replace', 'index' => 0, 'suggested' => 'x', 'rationale' => 'y'],
        ]]));
    }

    public function test_parse_review_response_rejects_a_non_remove_action_without_a_suggested_value(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires a string');

        $this->reviewer->parseReviewResponse(json_encode(['suggestions' => [
            ['category' => 'correction', 'field' => 'summary', 'action' => 'replace', 'rationale' => 'y'],
        ]]));
    }

    public function test_parse_review_response_rejects_a_non_cefr_english_level_value(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CEFR code');

        $this->reviewer->parseReviewResponse(json_encode(['suggestions' => [
            ['category' => 'improvement', 'field' => 'english_level', 'action' => 'replace', 'suggested' => 'Avanzado', 'rationale' => 'y'],
        ]]));
    }

    public function test_parse_review_response_accepts_a_valid_cefr_english_level_value(): void
    {
        $suggestions = $this->reviewer->parseReviewResponse(json_encode(['suggestions' => [
            ['category' => 'improvement', 'field' => 'english_level', 'action' => 'replace', 'suggested' => 'C1', 'rationale' => 'y'],
        ]]));

        $this->assertSame('C1', $suggestions[0]['suggested']);
    }

    public function test_parse_review_response_accepts_a_valid_remove_suggestion_with_no_suggested_value(): void
    {
        $suggestions = $this->reviewer->parseReviewResponse(json_encode(['suggestions' => [
            ['category' => 'improvement', 'field' => 'skills', 'action' => 'remove', 'index' => 2, 'current' => 'Cobol', 'rationale' => 'Ya no es relevante.'],
        ]]));

        $this->assertSame('sugg-1', $suggestions[0]['id']);
        $this->assertNull($suggestions[0]['suggested']);
    }

    public function test_apply_suggestions_replaces_and_removes_array_items_without_index_shift_bugs(): void
    {
        $profile = Profile::factory()->active()->create([
            'skills' => ['PHP', 'Laravel', 'jQuery', 'MySQL'],
        ]);

        $updated = $this->reviewer->applySuggestions($profile, [
            ['field' => 'skills', 'action' => 'replace', 'index' => 0, 'suggested' => 'PHP 8'],
            ['field' => 'skills', 'action' => 'remove', 'index' => 2, 'suggested' => null],
            ['field' => 'skills', 'action' => 'add', 'index' => null, 'suggested' => 'Docker'],
        ]);

        $this->assertSame(['PHP 8', 'Laravel', 'MySQL', 'Docker'], $updated->skills);
    }

    public function test_apply_suggestions_updates_scalar_fields_and_regenerates_markdown(): void
    {
        $profile = Profile::factory()->active()->create(['headline' => 'Old headline']);

        $updated = $this->reviewer->applySuggestions($profile, [
            ['field' => 'headline', 'action' => 'replace', 'index' => null, 'suggested' => 'New headline'],
        ]);

        $this->assertSame('New headline', $updated->headline);
        $this->assertStringContainsString('New headline', $updated->raw_md);
        $this->assertStringContainsString('New headline', file_get_contents($this->profilePath));
    }

    public function test_apply_suggestions_updates_english_level_and_language_items_independently(): void
    {
        $profile = Profile::factory()->active()->create([
            'languages' => ['items' => ['Español nativo'], 'english_level' => 'B1'],
        ]);

        $updated = $this->reviewer->applySuggestions($profile, [
            ['field' => 'english_level', 'action' => 'replace', 'index' => null, 'suggested' => 'B2'],
            ['field' => 'languages', 'action' => 'add', 'index' => null, 'suggested' => 'Portugués básico'],
        ]);

        $this->assertSame('B2', $updated->languages['english_level']);
        $this->assertSame(['Español nativo', 'Portugués básico'], $updated->languages['items']);
    }
}

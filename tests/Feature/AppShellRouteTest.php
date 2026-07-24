<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AppShellRouteTest extends TestCase
{
    public function test_root_redirects_to_marketplace(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/marketplace');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validModulePaths(): array
    {
        return [
            'marketplace' => ['/marketplace'],
            'marketplace detail' => ['/marketplace/1'],
            'tracking' => ['/tracking'],
            'tracking detail' => ['/tracking/1'],
            'profile' => ['/profile'],
            'design system' => ['/profile/design-system'],
        ];
    }

    #[DataProvider('validModulePaths')]
    public function test_it_renders_the_shell_for_module_paths(string $path): void
    {
        $response = $this->get($path);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('App/Shell'));
    }

    public function test_it_404s_for_unknown_top_level_paths(): void
    {
        $response = $this->get('/does-not-exist');

        $response->assertNotFound();
    }
}

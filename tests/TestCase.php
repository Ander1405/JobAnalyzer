<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected User $actingUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Must be persisted (not ::make()): BelongsToUser scopes every job_offers/
        // profiles/tracked_jobs query by auth()->id(), which is null for an
        // unsaved model — every route-scoped lookup would 404 against real rows.
        // Tests without RefreshDatabase never migrate, so there's no users table
        // to persist to — fall back to an unsaved instance for those.
        $this->actingUser = Schema::hasTable('users')
            ? User::factory()->create()
            : User::factory()->make(['id' => 1]);

        $this->actingAs($this->actingUser);
    }
}

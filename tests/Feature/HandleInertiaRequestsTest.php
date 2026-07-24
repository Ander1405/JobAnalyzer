<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class HandleInertiaRequestsTest extends TestCase
{
    public function test_it_shares_the_match_score_thresholds_with_every_page(): void
    {
        config([
            'jobhunter.match_score_alert_threshold' => 80,
            'jobhunter.min_match_to_publish' => 75,
        ]);

        $response = $this->get('/marketplace');

        $response->assertInertia(fn ($page) => $page
            ->where('matchScoreAlertThreshold', 80)
            ->where('minMatchToPublish', 75)
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Job;

interface AIProvider
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(string $perfilMd, Job $job): array;
}

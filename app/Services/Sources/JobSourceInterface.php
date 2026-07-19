<?php

declare(strict_types=1);

namespace App\Services\Sources;

use App\DTOs\JobOffer;
use Illuminate\Support\Collection;

interface JobSourceInterface
{
    /**
     * @return Collection<int, JobOffer>
     */
    public function fetch(): Collection;
}

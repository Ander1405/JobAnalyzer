<?php

declare(strict_types=1);

namespace App\Enums;

enum JobStatus: string
{
    case Fetched = 'fetched';
    case Analyzing = 'analyzing';
    case Analyzed = 'analyzed';
    case Published = 'published';
    case Failed = 'failed';

    /** Rejected by the profile pre-filter, before spending an AI call on it. */
    case Discarded = 'discarded';
}

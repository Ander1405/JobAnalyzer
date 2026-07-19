<?php

declare(strict_types=1);

namespace App\Enums;

enum JobStatus: string
{
    case Fetched = 'fetched';
    case Analyzed = 'analyzed';
    case Published = 'published';
    case Failed = 'failed';
}

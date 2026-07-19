<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $hash
 * @property string $source
 * @property string $company
 * @property string $title
 * @property string $description
 * @property string $url
 * @property string|null $contract_type
 * @property string|null $salary_raw
 * @property string|null $language
 * @property JobStatus $status
 * @property ApplicationStatus $application_status
 * @property string|null $ai_provider
 * @property array<string, mixed>|null $ai_analysis
 * @property string|null $notion_page_id
 * @property string|null $error_message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('job_offers')]
#[Fillable([
    'hash',
    'source',
    'company',
    'title',
    'description',
    'url',
    'contract_type',
    'salary_raw',
    'language',
    'status',
    'application_status',
    'ai_provider',
    'ai_analysis',
    'notion_page_id',
    'error_message',
])]
class Job extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => JobStatus::class,
            'application_status' => ApplicationStatus::class,
            'ai_analysis' => 'array',
        ];
    }
}

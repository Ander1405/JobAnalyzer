<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Models\Concerns\BelongsToUser;
use Database\Factories\JobFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $hash
 * @property string $source
 * @property string $company
 * @property string $title
 * @property string $description
 * @property string $url
 * @property string|null $contract_type
 * @property string|null $salary_raw
 * @property string|null $language
 * @property string|null $apply_url
 * @property string|null $location
 * @property bool|null $is_remote
 * @property string|null $work_mode
 * @property string|null $seniority
 * @property string|null $employment_type
 * @property Carbon|null $posted_at
 * @property Carbon|null $expires_at
 * @property string|null $company_logo
 * @property string|null $company_website
 * @property array<int, string>|null $benefits
 * @property array<int, string>|null $required_skills
 * @property int|null $applicants_count
 * @property JobStatus $status
 * @property ApplicationStatus $application_status
 * @property string|null $ai_provider
 * @property array<string, mixed>|null $ai_analysis
 * @property string|null $ai_model
 * @property int|null $ai_duration_ms
 * @property int|null $ai_input_tokens
 * @property int|null $ai_output_tokens
 * @property float|null $ai_cost_usd
 * @property string|null $notion_page_id
 * @property string|null $error_message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TrackedJob|null $trackedJob
 * @property-read Profile|null $latestCvVariant
 */
#[Table('job_offers')]
#[Fillable([
    'user_id',
    'hash',
    'source',
    'company',
    'title',
    'description',
    'url',
    'contract_type',
    'salary_raw',
    'language',
    'apply_url',
    'location',
    'is_remote',
    'work_mode',
    'seniority',
    'employment_type',
    'posted_at',
    'expires_at',
    'company_logo',
    'company_website',
    'benefits',
    'required_skills',
    'applicants_count',
    'status',
    'application_status',
    'ai_provider',
    'ai_analysis',
    'ai_model',
    'ai_duration_ms',
    'ai_input_tokens',
    'ai_output_tokens',
    'ai_cost_usd',
    'notion_page_id',
    'error_message',
])]
class Job extends Model
{
    /** @use HasFactory<JobFactory> */
    use BelongsToUser, HasFactory;

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
            'ai_cost_usd' => 'float',
            'is_remote' => 'boolean',
            'posted_at' => 'datetime',
            'expires_at' => 'datetime',
            'benefits' => 'array',
            'required_skills' => 'array',
            'applicants_count' => 'integer',
        ];
    }

    /**
     * @return HasOne<TrackedJob, $this>
     */
    public function trackedJob(): HasOne
    {
        return $this->hasOne(TrackedJob::class);
    }

    /**
     * @return HasMany<Profile, $this>
     */
    public function cvVariants(): HasMany
    {
        return $this->hasMany(Profile::class);
    }

    /**
     * The tailored CV shown for this job — a job can be re-tailored more than
     * once, so this is the latest confirmed variant, not a unique 1:1 link.
     *
     * @return HasOne<Profile, $this>
     */
    public function latestCvVariant(): HasOne
    {
        return $this->hasOne(Profile::class)->latestOfMany();
    }
}

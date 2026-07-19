<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $label
 * @property array<string, mixed>|null $contact
 * @property string|null $headline
 * @property string|null $summary
 * @property array<int, string>|null $experience
 * @property array<int, string>|null $skills
 * @property array<int, string>|null $education
 * @property array{items: array<int, string>, english_level: string|null}|null $languages
 * @property array<int, string>|null $certifications
 * @property string $raw_md
 * @property string|null $source_text
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('profiles')]
#[Fillable([
    'slug',
    'label',
    'contact',
    'headline',
    'summary',
    'experience',
    'skills',
    'education',
    'languages',
    'certifications',
    'raw_md',
    'source_text',
    'is_active',
])]
class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contact' => 'array',
            'experience' => 'array',
            'skills' => 'array',
            'education' => 'array',
            'languages' => 'array',
            'certifications' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public static function active(): ?self
    {
        return static::where('is_active', true)->first();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

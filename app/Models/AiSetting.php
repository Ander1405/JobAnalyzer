<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $provider
 * @property string|null $model
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['provider', 'model'])]
class AiSetting extends Model
{
    public static function current(): self
    {
        return self::firstOrCreate(
            ['id' => 1],
            ['provider' => config('jobhunter.ai_provider'), 'model' => null],
        );
    }
}

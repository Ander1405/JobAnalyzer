<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RequiresUserContext;
use App\Services\Profile\ProfileVariantService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('profile:sync {--user= : Email of the user whose active profile to sync}')]
#[Description('Re-parse a hand-edited storage/app/perfil.md back into the active profile (deterministic, no AI).')]
class ProfileSync extends Command
{
    use RequiresUserContext;

    public function handle(ProfileVariantService $service): int
    {
        if ($this->resolveUserOption() === null) {
            return self::FAILURE;
        }

        try {
            $profile = $service->syncActiveFromFile();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Profile [{$profile->slug}] synced from storage/app/perfil.md.");

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * `perfil.md` is a manual-edit staging file, one per user — without this, two
 * users editing their CV at the same time would read and overwrite each other's
 * file, since it used to be a single shared path (storage/app/perfil.md).
 */
final class ProfileFile
{
    public static function path(): string
    {
        $userId = auth()->id();

        if ($userId === null) {
            throw new RuntimeException('No hay un usuario autenticado para resolver perfil.md.');
        }

        return storage_path("app/perfil_{$userId}.md");
    }
}

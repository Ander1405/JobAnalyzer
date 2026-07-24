<?php

declare(strict_types=1);

namespace App\Enums;

enum CommentType: string
{
    case Nota = 'nota';
    case CambioEstado = 'cambio_estado';
    case Entrevista = 'entrevista';
    case Seguimiento = 'seguimiento';

    public function label(): string
    {
        return match ($this) {
            self::Nota => 'Nota',
            self::CambioEstado => 'Cambio de estado',
            self::Entrevista => 'Entrevista',
            self::Seguimiento => 'Seguimiento',
        };
    }
}

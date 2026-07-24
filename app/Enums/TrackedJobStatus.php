<?php

declare(strict_types=1);

namespace App\Enums;

enum TrackedJobStatus: string
{
    case SinAplicar = 'sin_aplicar';
    case Aplique = 'aplique';
    case EnProceso = 'en_proceso';
    case Rechazado = 'rechazado';
    case Oferta = 'oferta';

    public function label(): string
    {
        return match ($this) {
            self::SinAplicar => 'Sin aplicar',
            self::Aplique => 'Apliqué',
            self::EnProceso => 'En proceso',
            self::Rechazado => 'Rechazado',
            self::Oferta => 'Oferta',
        };
    }
}

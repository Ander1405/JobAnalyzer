<?php

declare(strict_types=1);

namespace App\Enums;

enum ApplicationStatus: string
{
    case New = 'Nueva';
    case CvAdapted = 'CV adaptado';
    case Applied = 'Aplicada';
    case Interview = 'Entrevista';
    case Closed = 'Cerrada';
}

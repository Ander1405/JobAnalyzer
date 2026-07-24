<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class CvAtsResult
{
    /**
     * @param  array<int, string>  $problemas
     * @param  array<int, string>  $keywordsFaltantes
     * @param  array<int, string>  $recomendacionesFormato
     */
    public function __construct(
        public int $atsScore,
        public array $problemas,
        public array $keywordsFaltantes,
        public array $recomendacionesFormato,
        public string $versionOptimizadaMd,
        public AiUsage $usage,
        public string $model,
    ) {}
}

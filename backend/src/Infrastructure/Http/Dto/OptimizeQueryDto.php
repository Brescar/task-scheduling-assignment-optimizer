<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

final readonly class OptimizeQueryDto
{
    public function __construct(
        public string $variant = 'cost',
    ) {}
}


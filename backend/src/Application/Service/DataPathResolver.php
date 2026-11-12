<?php

declare(strict_types=1);

namespace App\Application\Service;

final readonly class DataPathResolver
{
    public function __construct(
        private string $projectDir = '',
    ) {}

    public function resolve(string $filename): string
    {
        $candidates = [
            $this->projectDir . '/../data/' . $filename,
            $this->projectDir . '/data/' . $filename,
            getcwd() . '/data/' . $filename,
            dirname(__DIR__, 4) . '/data/' . $filename,
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return realpath($candidate) ?: $candidate;
            }
        }

        return 'data/' . $filename;
    }
}


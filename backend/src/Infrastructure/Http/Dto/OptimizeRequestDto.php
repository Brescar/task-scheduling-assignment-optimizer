<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

final readonly class OptimizeRequestDto
{
    /**
     * @param array<array{employeeId: int, skillLevel: int, hourlyRate: float}> $employees
     * @param array<array{taskId: int, skillLevel: int, estimation: float}> $tasks
     */
    public function __construct(
        public array $employees = [],
        public array $tasks = [],
    ) {}

    public function isEmpty(): bool
    {
        return empty($this->employees) && empty($this->tasks);
    }

    /**
     * @return array{employees: array<mixed>, tasks: array<mixed>}
     */
    public function toPayloadArray(): array
    {
        return [
            'employees' => $this->employees,
            'tasks' => $this->tasks,
        ];
    }
}


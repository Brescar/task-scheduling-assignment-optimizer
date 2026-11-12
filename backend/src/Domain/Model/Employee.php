<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Immutable domain entity representing an employee.
 */
final readonly class Employee implements \JsonSerializable
{
    public function __construct(
        public int $id,
        public int $skillLevel,
        public float $hourlyRate,
    ) {
        if ($this->id <= 0) {
            throw new \InvalidArgumentException(sprintf('Employee ID must be a positive integer, got %d.', $this->id));
        }

        if ($this->skillLevel < 1) {
            throw new \InvalidArgumentException(sprintf('Employee skill level must be at least 1, got %d.', $this->skillLevel));
        }

        if ($this->hourlyRate < 0.0) {
            throw new \InvalidArgumentException(sprintf('Employee hourly rate cannot be negative, got %.2f.', $this->hourlyRate));
        }
    }

    public function canExecute(Task $task): bool
    {
        return $this->skillLevel >= $task->skillLevel;
    }

    public function jsonSerialize(): array
    {
        return [
            'employeeId' => $this->id,
            'skillLevel' => $this->skillLevel,
            'hourlyRate' => $this->hourlyRate,
        ];
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}

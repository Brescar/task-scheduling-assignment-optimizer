<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Value object representing an employee's aggregated workload.
 */
final readonly class EmployeeWorkload implements \JsonSerializable
{
    /**
     * @param Task[] $assignedTasks
     */
    public function __construct(
        public int $employeeId,
        public float $totalAssignedHours,
        public array $assignedTasks = [],
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'employeeId' => $this->employeeId,
            'totalAssignedHours' => $this->totalAssignedHours,
            'taskCount' => count($this->assignedTasks),
        ];
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}

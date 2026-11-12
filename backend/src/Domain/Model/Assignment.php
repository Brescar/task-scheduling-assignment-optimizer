<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Value object representing the assignment of a Task to an Employee.
 */
final readonly class Assignment implements \JsonSerializable
{
    public float $cost;

    public function __construct(
        public Task $task,
        public Employee $employee,
    ) {
        if (!$this->employee->canExecute($this->task)) {
            throw new \LogicException(sprintf(
                'Invalid assignment: Employee #%d (skill %d) cannot execute Task #%d (requires skill %d).',
                $this->employee->id,
                $this->employee->skillLevel,
                $this->task->id,
                $this->task->skillLevel
            ));
        }

        $this->cost = round($this->employee->hourlyRate * $this->task->estimation, 2);
    }

    public function jsonSerialize(): array
    {
        return [
            'taskId' => $this->task->id,
            'requiredSkill' => $this->task->skillLevel,
            'hours' => $this->task->estimation,
            'employeeId' => $this->employee->id,
            'employeeSkill' => $this->employee->skillLevel,
            'hourlyRate' => $this->employee->hourlyRate,
            'cost' => $this->cost,
        ];
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}

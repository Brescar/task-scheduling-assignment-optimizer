<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Model\Employee;
use App\Domain\Model\Task;

trait OptimizationTestHelper
{
    /**
     * Loads the official 10 employees from data/employees.json.
     *
     * @return Employee[]
     */
    protected function loadOfficialEmployees(): array
    {
        $path = __DIR__ . '/../../../../data/employees.json';
        if (!file_exists($path)) {
            $path = dirname(__DIR__, 3) . '/data/employees.json';
        }

        $raw = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return array_map(
            static fn(array $item): Employee => new Employee(
                id: (int) $item['employeeId'],
                skillLevel: (int) $item['skillLevel'],
                hourlyRate: (float) $item['hourlyRate'],
            ),
            $raw
        );
    }

    /**
     * Loads the official 30 tasks from data/tasks.json.
     *
     * @return Task[]
     */
    protected function loadOfficialTasks(): array
    {
        $path = __DIR__ . '/../../../../data/tasks.json';
        if (!file_exists($path)) {
            $path = dirname(__DIR__, 3) . '/data/tasks.json';
        }

        $raw = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return array_map(
            static fn(array $item): Task => new Task(
                id: (int) $item['taskId'],
                skillLevel: (int) $item['skillLevel'],
                estimation: (float) $item['estimation'],
            ),
            $raw
        );
    }
}


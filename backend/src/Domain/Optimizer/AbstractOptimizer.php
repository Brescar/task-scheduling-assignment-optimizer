<?php

declare(strict_types=1);

namespace App\Domain\Optimizer;

use App\Domain\Contract\OptimizerInterface;
use App\Domain\Model\Assignment;
use App\Domain\Model\Employee;
use App\Domain\Model\EmployeeWorkload;
use App\Domain\Model\OptimizationResult;
use App\Domain\Model\Task;

/**
 * Base optimizer providing common feasibility checks and metrics aggregation.
 */
abstract class AbstractOptimizer implements OptimizerInterface
{
    /**
     * Checks if all tasks have at least one eligible employee.
     *
     * @param Employee[] $employees
     * @param Task[] $tasks
     * @return int[] Unassignable task IDs (empty if problem is feasible)
     */
    protected function findUnassignableTasks(array $employees, array $tasks): array
    {
        if (empty($employees)) {
            return array_map(static fn(Task $t): int => $t->id, $tasks);
        }

        $unassignable = [];
        foreach ($tasks as $task) {
            $hasEligible = false;
            foreach ($employees as $employee) {
                if ($employee->canExecute($task)) {
                    $hasEligible = true;
                    break;
                }
            }

            if (!$hasEligible) {
                $unassignable[] = $task->id;
            }
        }

        return $unassignable;
    }

    /**
     * Compiles final metrics (totalPrice, makespan, and employee summaries).
     *
     * @param Employee[] $employees
     * @param Assignment[] $assignments
     * @return array{totalPrice: float, makespan: float, summaries: EmployeeWorkload[]}
     */
    protected function compileMetrics(array $employees, array $assignments): array
    {
        $workloads = [];
        $tasksByEmployee = [];

        foreach ($employees as $employee) {
            $workloads[$employee->id] = 0.0;
            $tasksByEmployee[$employee->id] = [];
        }

        $totalPrice = 0.0;

        foreach ($assignments as $assignment) {
            $empId = $assignment->employee->id;
            $workloads[$empId] += $assignment->task->estimation;
            $tasksByEmployee[$empId][] = $assignment->task;
            $totalPrice += $assignment->cost;
        }

        $makespan = 0.0;
        foreach ($workloads as $load) {
            if ($load > $makespan) {
                $makespan = $load;
            }
        }

        ksort($workloads);

        $summaries = [];
        foreach ($workloads as $empId => $totalHours) {
            $summaries[] = new EmployeeWorkload(
                employeeId: $empId,
                totalAssignedHours: round($totalHours, 2),
                assignedTasks: $tasksByEmployee[$empId] ?? [],
            );
        }

        return [
            'totalPrice' => round($totalPrice, 2),
            'makespan' => round($makespan, 2),
            'summaries' => $summaries,
        ];
    }
}

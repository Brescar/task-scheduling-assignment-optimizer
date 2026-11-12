<?php

declare(strict_types=1);

namespace App\Domain\Optimizer;

use App\Domain\Model\Assignment;
use App\Domain\Model\Employee;
use App\Domain\Model\OptimizationResult;
use App\Domain\Model\Task;

/**
 * Variant A: Minimum Total Cost Optimizer.
 *
 * Mathematically decouples tasks to minimize sum(hourlyRate * hours) by greedily
 * selecting the lowest-rate eligible employee for each task, breaking ties by lowest
 * current workload, then lowest employee ID for determinism.
 */
final class MinCostOptimizer extends AbstractOptimizer
{
    public function getVariant(): string
    {
        return OptimizationResult::VARIANT_COST;
    }

    /**
     * @param Employee[] $employees
     * @param Task[] $tasks
     */
    public function optimize(array $employees, array $tasks): OptimizationResult
    {
        $unassignable = $this->findUnassignableTasks($employees, $tasks);
        if (!empty($unassignable)) {
            return OptimizationResult::infeasible($this->getVariant(), $unassignable);
        }

        if (empty($tasks)) {
            $metrics = $this->compileMetrics($employees, []);
            return new OptimizationResult(
                status: OptimizationResult::STATUS_FEASIBLE,
                variant: $this->getVariant(),
                assignments: [],
                totalPrice: 0.0,
                makespan: 0.0,
                employeeSummaries: $metrics['summaries'],
            );
        }

        $sortedTasks = $tasks;
        usort($sortedTasks, static fn(Task $a, Task $b): int => $a->id <=> $b->id);

        $currentWorkloads = [];
        foreach ($employees as $employee) {
            $currentWorkloads[$employee->id] = 0.0;
        }

        $assignments = [];

        foreach ($sortedTasks as $task) {
            $eligible = array_filter(
                $employees,
                static fn(Employee $e): bool => $e->canExecute($task)
            );

            // Deterministic selection:
            // 1) Lowest hourlyRate
            // 2) Lowest accumulated workload (spread load among equal-rate workers)
            // 3) Lowest employeeId
            usort($eligible, static function (Employee $a, Employee $b) use ($currentWorkloads): int {
                $rateComp = $a->hourlyRate <=> $b->hourlyRate;
                if ($rateComp !== 0) {
                    return $rateComp;
                }

                $loadComp = $currentWorkloads[$a->id] <=> $currentWorkloads[$b->id];
                if ($loadComp !== 0) {
                    return $loadComp;
                }

                return $a->id <=> $b->id;
            });

            /** @var Employee $bestEmployee */
            $bestEmployee = reset($eligible);

            $assignments[] = new Assignment($task, $bestEmployee);
            $currentWorkloads[$bestEmployee->id] += $task->estimation;
        }

        usort($assignments, static fn(Assignment $a, Assignment $b): int => $a->task->id <=> $b->task->id);

        $metrics = $this->compileMetrics($employees, $assignments);

        return new OptimizationResult(
            status: OptimizationResult::STATUS_FEASIBLE,
            variant: $this->getVariant(),
            assignments: $assignments,
            totalPrice: $metrics['totalPrice'],
            makespan: $metrics['makespan'],
            employeeSummaries: $metrics['summaries'],
        );
    }
}

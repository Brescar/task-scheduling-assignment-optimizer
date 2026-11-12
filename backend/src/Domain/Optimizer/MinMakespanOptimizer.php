<?php

declare(strict_types=1);

namespace App\Domain\Optimizer;

use App\Domain\Model\Assignment;
use App\Domain\Model\Employee;
use App\Domain\Model\OptimizationResult;
use App\Domain\Model\Task;

/**
 * Variant B: Minimum Completion Time (Makespan) Optimizer.
 *
 * Implements the Restricted Longest Processing Time (LPT) Heuristic for the
 * NP-hard multiprocessor scheduling problem with eligibility constraints (P | eligibility | Cmax).
 *
 * Sorts tasks descending by duration and greedily places each task onto the eligible
 * employee with the lowest accumulated workload, breaking ties deterministically.
 */
final class MinMakespanOptimizer extends AbstractOptimizer
{
    public function getVariant(): string
    {
        return OptimizationResult::VARIANT_MAKESPAN;
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

        // Restricted LPT task ordering:
        // 1) estimation DESC (longest tasks scheduled first)
        // 2) skillLevel DESC (harder tasks placed first)
        // 3) taskId ASC (deterministic)
        $sortedTasks = $tasks;
        usort($sortedTasks, static function (Task $a, Task $b): int {
            $estComp = $b->estimation <=> $a->estimation;
            if ($estComp !== 0) {
                return $estComp;
            }

            $skillComp = $b->skillLevel <=> $a->skillLevel;
            if ($skillComp !== 0) {
                return $skillComp;
            }

            return $a->id <=> $b->id;
        });

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

            // Deterministic employee selection:
            // 1) Lowest current accumulated workload (minimizes makespan)
            // 2) Lowest hourlyRate (cost-effective secondary objective)
            // 3) Lowest employeeId (strict determinism)
            usort($eligible, static function (Employee $a, Employee $b) use ($currentWorkloads): int {
                $loadComp = $currentWorkloads[$a->id] <=> $currentWorkloads[$b->id];
                if ($loadComp !== 0) {
                    return $loadComp;
                }

                $rateComp = $a->hourlyRate <=> $b->hourlyRate;
                if ($rateComp !== 0) {
                    return $rateComp;
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

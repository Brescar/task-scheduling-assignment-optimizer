<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Model\Employee;
use App\Domain\Model\OptimizationResult;
use App\Domain\Model\Task;
use App\Domain\Optimizer\MinCostOptimizer;
use PHPUnit\Framework\TestCase;

/**
 * Unit test suite for MinCostOptimizer (Variant A).
 * Verifies cost-optimal assignment, eligibility constraints, load balancing across
 * equal-cost workers, and 100% deterministic output.
 */
final class MinCostOptimizerTest extends TestCase
{
    use OptimizationTestHelper;

    private MinCostOptimizer $optimizer;

    protected function setUp(): void
    {
        $this->optimizer = new MinCostOptimizer();
    }

    /**
     * Verifies that the optimizer identifies unassignable tasks and returns an
     * infeasible result when employee skill levels are insufficient.
     */
    public function testFeasibilityFailureWithUnassignableTask(): void
    {
        // Arrange
        $employees = [
            new Employee(id: 1, skillLevel: 1, hourlyRate: 100.0),
            new Employee(id: 2, skillLevel: 2, hourlyRate: 200.0),
        ];

        $tasks = [
            new Task(id: 1, skillLevel: 1, estimation: 2.0),
            new Task(id: 2, skillLevel: 3, estimation: 5.0),
            new Task(id: 3, skillLevel: 4, estimation: 1.0),
        ];

        // Act
        $result = $this->optimizer->optimize($employees, $tasks);

        // Assert
        $this->assertFalse($result->isFeasible());
        $this->assertSame(OptimizationResult::STATUS_INFEASIBLE, $result->status);
        $this->assertSame(OptimizationResult::VARIANT_COST, $result->variant);
        $this->assertSame([2, 3], $result->unassignableTaskIds);
        $this->assertEmpty($result->assignments);
        $this->assertSame(0.0, $result->totalPrice);
    }

    /**
     * Verifies that scheduling an empty task list succeeds cleanly with zero cost and zero makespan.
     */
    public function testEmptyTasksInput(): void
    {
        // Arrange
        $employees = [new Employee(id: 1, skillLevel: 1, hourlyRate: 100.0)];

        // Act
        $result = $this->optimizer->optimize($employees, []);

        // Assert
        $this->assertTrue($result->isFeasible());
        $this->assertSame(0.0, $result->totalPrice);
        $this->assertSame(0.0, $result->makespan);
        $this->assertCount(0, $result->assignments);
        $this->assertCount(1, $result->employeeSummaries);
        $this->assertSame(0.0, $result->employeeSummaries[0]->totalAssignedHours);
    }

    /**
     * Verifies execution against the official Beijer Ref dataset: all tasks are assigned,
     * skill eligibility is respected for every assignment, and total hours match.
     */
    public function testOfficialDatasetExecution(): void
    {
        // Arrange
        $employees = $this->loadOfficialEmployees();
        $tasks = $this->loadOfficialTasks();

        // Act
        $result = $this->optimizer->optimize($employees, $tasks);

        // Assert
        $this->assertTrue($result->isFeasible());
        $this->assertSame(OptimizationResult::VARIANT_COST, $result->variant);
        $this->assertCount(30, $result->assignments);

        foreach ($result->assignments as $assignment) {
            $this->assertGreaterThanOrEqual(
                $assignment->task->skillLevel,
                $assignment->employee->skillLevel,
                sprintf('Employee #%d cannot execute Task #%d', $assignment->employee->id, $assignment->task->id)
            );
        }

        $totalHours = array_sum(array_map(static fn(Task $t) => $t->estimation, $tasks));
        $assignedHours = array_sum(array_map(
            static fn($summary) => $summary->totalAssignedHours,
            $result->employeeSummaries
        ));
        $this->assertEqualsWithDelta($totalHours, $assignedHours, 0.001);
        $this->assertGreaterThan(0.0, $result->totalPrice);
        $this->assertGreaterThan(0.0, $result->makespan);
    }

    /**
     * Verifies that when multiple workers share the same minimum hourly rate,
     * the optimizer evenly balances the workload across them as a secondary objective.
     */
    public function testLoadBalancingAmongEqualCostWorkers(): void
    {
        // Arrange
        $employees = [
            new Employee(id: 1, skillLevel: 1, hourlyRate: 100.0),
            new Employee(id: 2, skillLevel: 1, hourlyRate: 100.0),
        ];

        $tasks = [
            new Task(id: 1, skillLevel: 1, estimation: 2.0),
            new Task(id: 2, skillLevel: 1, estimation: 2.0),
            new Task(id: 3, skillLevel: 1, estimation: 2.0),
            new Task(id: 4, skillLevel: 1, estimation: 2.0),
        ];

        // Act
        $result = $this->optimizer->optimize($employees, $tasks);

        // Assert
        $this->assertTrue($result->isFeasible());
        $this->assertSame(4.0, $result->employeeSummaries[0]->totalAssignedHours);
        $this->assertSame(4.0, $result->employeeSummaries[1]->totalAssignedHours);
        $this->assertSame(4.0, $result->makespan);
        $this->assertSame(800.0, $result->totalPrice);
    }

    /**
     * Verifies 100% deterministic output regardless of input array ordering across multiple runs.
     */
    public function testDeterminismWithRandomizedInputOrder(): void
    {
        // Arrange
        $employees = $this->loadOfficialEmployees();
        $tasks = $this->loadOfficialTasks();

        // Act
        $baseline = $this->optimizer->optimize($employees, $tasks);

        // Assert
        for ($i = 0; $i < 5; $i++) {
            $shuffledEmployees = $employees;
            $shuffledTasks = $tasks;
            shuffle($shuffledEmployees);
            shuffle($shuffledTasks);

            $run = $this->optimizer->optimize($shuffledEmployees, $shuffledTasks);

            $this->assertSame($baseline->totalPrice, $run->totalPrice);
            $this->assertSame($baseline->makespan, $run->makespan);
            $this->assertSame($baseline->toArray()['assignments'], $run->toArray()['assignments']);
        }
    }
}

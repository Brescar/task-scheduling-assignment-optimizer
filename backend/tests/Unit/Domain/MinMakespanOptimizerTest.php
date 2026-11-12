<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Model\Employee;
use App\Domain\Model\OptimizationResult;
use App\Domain\Model\Task;
use App\Domain\Optimizer\MinCostOptimizer;
use App\Domain\Optimizer\MinMakespanOptimizer;
use PHPUnit\Framework\TestCase;

/**
 * Unit test suite for MinMakespanOptimizer (Variant B).
 * Verifies Restricted LPT makespan minimization, comparison vs Min Cost,
 * deterministic tie-breaking, and even distribution across eligible workers.
 */
final class MinMakespanOptimizerTest extends TestCase
{
    use OptimizationTestHelper;

    private MinMakespanOptimizer $makespanOptimizer;
    private MinCostOptimizer $costOptimizer;

    protected function setUp(): void
    {
        $this->makespanOptimizer = new MinMakespanOptimizer();
        $this->costOptimizer = new MinCostOptimizer();
    }

    /**
     * Verifies that the optimizer identifies unassignable tasks and returns an
     * infeasible result when task skill requirements cannot be met by any employee.
     */
    public function testFeasibilityFailure(): void
    {
        // Arrange
        $employees = [new Employee(id: 1, skillLevel: 1, hourlyRate: 100.0)];
        $tasks = [new Task(id: 99, skillLevel: 2, estimation: 4.0)];

        // Act
        $result = $this->makespanOptimizer->optimize($employees, $tasks);

        // Assert
        $this->assertFalse($result->isFeasible());
        $this->assertSame(OptimizationResult::STATUS_INFEASIBLE, $result->status);
        $this->assertSame([99], $result->unassignableTaskIds);
    }

    /**
     * Verifies that the makespan optimizer achieves a significantly lower completion time
     * than the cost optimizer on the official dataset, while maintaining task eligibility.
     */
    public function testOfficialDatasetMakespanIsLowerThanMinCost(): void
    {
        // Arrange
        $employees = $this->loadOfficialEmployees();
        $tasks = $this->loadOfficialTasks();

        // Act
        $costResult = $this->costOptimizer->optimize($employees, $tasks);
        $makespanResult = $this->makespanOptimizer->optimize($employees, $tasks);

        // Assert
        $this->assertTrue($costResult->isFeasible());
        $this->assertTrue($makespanResult->isFeasible());

        $this->assertLessThan(
            $costResult->makespan,
            $makespanResult->makespan,
            sprintf('Makespan optimizer (%.2f h) should achieve lower makespan than Cost optimizer (%.2f h)',
                $makespanResult->makespan,
                $costResult->makespan
            )
        );

        $this->assertLessThanOrEqual(
            $makespanResult->totalPrice,
            $costResult->totalPrice,
            sprintf('Min Cost total price (%.2f) must be <= Min Makespan total price (%.2f)',
                $costResult->totalPrice,
                $makespanResult->totalPrice
            )
        );

        $this->assertCount(30, $makespanResult->assignments);

        foreach ($makespanResult->assignments as $assignment) {
            $this->assertGreaterThanOrEqual(
                $assignment->task->skillLevel,
                $assignment->employee->skillLevel
            );
        }
    }

    /**
     * Verifies that the makespan optimizer generates identical, deterministic assignments
     * across repeated runs with shuffled input orderings.
     */
    public function testDeterminismWithRandomizedInputs(): void
    {
        // Arrange
        $employees = $this->loadOfficialEmployees();
        $tasks = $this->loadOfficialTasks();

        // Act
        $baseline = $this->makespanOptimizer->optimize($employees, $tasks);

        // Assert
        for ($i = 0; $i < 5; $i++) {
            $shuffledEmployees = $employees;
            $shuffledTasks = $tasks;
            shuffle($shuffledEmployees);
            shuffle($shuffledTasks);

            $run = $this->makespanOptimizer->optimize($shuffledEmployees, $shuffledTasks);

            $this->assertSame($baseline->totalPrice, $run->totalPrice);
            $this->assertSame($baseline->makespan, $run->makespan);
            $this->assertSame($baseline->toArray()['assignments'], $run->toArray()['assignments']);
        }
    }

    /**
     * Verifies that equal-sized tasks are perfectly distributed one-by-one across
     * equally skilled workers, achieving optimal makespan.
     */
    public function testWorkloadDistributionAcrossEligibleWorkers(): void
    {
        // Arrange
        $employees = [
            new Employee(id: 1, skillLevel: 2, hourlyRate: 100.0),
            new Employee(id: 2, skillLevel: 2, hourlyRate: 150.0),
            new Employee(id: 3, skillLevel: 2, hourlyRate: 200.0),
        ];

        $tasks = [
            new Task(id: 1, skillLevel: 2, estimation: 6.0),
            new Task(id: 2, skillLevel: 2, estimation: 6.0),
            new Task(id: 3, skillLevel: 2, estimation: 6.0),
        ];

        // Act
        $result = $this->makespanOptimizer->optimize($employees, $tasks);

        // Assert
        $this->assertTrue($result->isFeasible());
        $this->assertSame(6.0, $result->makespan);
        foreach ($result->employeeSummaries as $summary) {
            $this->assertSame(6.0, $summary->totalAssignedHours);
        }
    }
}

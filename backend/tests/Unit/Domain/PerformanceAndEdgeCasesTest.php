<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Model\Employee;
use App\Domain\Model\Task;
use App\Domain\Optimizer\MinCostOptimizer;
use App\Domain\Optimizer\MinMakespanOptimizer;
use PHPUnit\Framework\TestCase;

/**
 * Edge cases and benchmark performance tests.
 * Validates assignment requirements:
 * - c.ii: Widely varying hourly rates
 * - c.iii: Highly skewed task durations
 * - c.iv: 50-200 tasks across 5-30 employees stress check (< 50ms)
 */
final class PerformanceAndEdgeCasesTest extends TestCase
{
    private MinCostOptimizer $costOptimizer;
    private MinMakespanOptimizer $makespanOptimizer;

    protected function setUp(): void
    {
        $this->costOptimizer = new MinCostOptimizer();
        $this->makespanOptimizer = new MinMakespanOptimizer();
    }

    /**
     * Requirement c.ii: Widely varying hourly rates ($10 vs $5000).
     * Min Cost must concentrate work on the $10/h worker; Min Makespan must split
     * work evenly to minimize completion time regardless of price disparity.
     */
    public function testWidelyVaryingHourlyRates(): void
    {
        // Arrange
        $employees = [
            new Employee(id: 1, skillLevel: 1, hourlyRate: 10.0),
            new Employee(id: 2, skillLevel: 1, hourlyRate: 5000.0),
        ];

        $tasks = [
            new Task(id: 1, skillLevel: 1, estimation: 10.0),
            new Task(id: 2, skillLevel: 1, estimation: 10.0),
        ];

        // Act
        $costResult = $this->costOptimizer->optimize($employees, $tasks);
        $makespanResult = $this->makespanOptimizer->optimize($employees, $tasks);

        // Assert
        $this->assertSame(200.0, $costResult->totalPrice);
        $this->assertSame(20.0, $costResult->makespan);
        $this->assertSame(20.0, $costResult->employeeSummaries[0]->totalAssignedHours);
        $this->assertSame(0.0, $costResult->employeeSummaries[1]->totalAssignedHours);

        $this->assertSame(10.0, $makespanResult->makespan);
        $this->assertSame(10.0, $makespanResult->employeeSummaries[0]->totalAssignedHours);
        $this->assertSame(10.0, $makespanResult->employeeSummaries[1]->totalAssignedHours);
    }

    /**
     * Requirement c.iii: Highly skewed task durations (one 100h task and ten 1h tasks).
     * The makespan optimizer must isolate the 100h bottleneck on one worker and distribute
     * the remaining smaller tasks among the other available workers.
     */
    public function testHighlySkewedTaskDurations(): void
    {
        // Arrange
        $employees = [
            new Employee(id: 1, skillLevel: 1, hourlyRate: 100.0),
            new Employee(id: 2, skillLevel: 1, hourlyRate: 100.0),
            new Employee(id: 3, skillLevel: 1, hourlyRate: 100.0),
        ];

        $tasks = [
            new Task(id: 1, skillLevel: 1, estimation: 100.0),
        ];
        for ($i = 2; $i <= 11; $i++) {
            $tasks[] = new Task(id: $i, skillLevel: 1, estimation: 1.0);
        }

        // Act
        $makespanResult = $this->makespanOptimizer->optimize($employees, $tasks);

        // Assert
        $this->assertTrue($makespanResult->isFeasible());
        $this->assertSame(100.0, $makespanResult->makespan);

        $emp100 = null;
        foreach ($makespanResult->employeeSummaries as $summary) {
            if ($summary->totalAssignedHours >= 100.0) {
                $emp100 = $summary;
                break;
            }
        }
        $this->assertNotNull($emp100);
        $this->assertSame(100.0, $emp100->totalAssignedHours);
    }

    /**
     * Requirement c.iv: Performance benchmark with 200 tasks and 30 employees.
     * Both optimizers must execute within 50 milliseconds without memory pressure.
     */
    public function testPerformanceCheck200TasksAnd30Employees(): void
    {
        // Arrange
        $employees = [];
        for ($e = 1; $e <= 30; $e++) {
            $employees[] = new Employee(
                id: $e,
                skillLevel: ($e % 3) + 1,
                hourlyRate: 50.0 + ($e * 20.0)
            );
        }

        $tasks = [];
        for ($t = 1; $t <= 200; $t++) {
            $tasks[] = new Task(
                id: $t,
                skillLevel: ($t % 3) + 1,
                estimation: (float) (($t % 10) + 1)
            );
        }

        // Act & Assert (Min Cost)
        $startCost = microtime(true);
        $costResult = $this->costOptimizer->optimize($employees, $tasks);
        $durationCostMs = (microtime(true) - $startCost) * 1000.0;

        $this->assertTrue($costResult->isFeasible());
        $this->assertCount(200, $costResult->assignments);
        $this->assertLessThan(
            50.0,
            $durationCostMs,
            sprintf('MinCostOptimizer took %.2f ms, expected < 50 ms', $durationCostMs)
        );

        // Act & Assert (Min Makespan)
        $startMakespan = microtime(true);
        $makespanResult = $this->makespanOptimizer->optimize($employees, $tasks);
        $durationMakespanMs = (microtime(true) - $startMakespan) * 1000.0;

        $this->assertTrue($makespanResult->isFeasible());
        $this->assertCount(200, $makespanResult->assignments);
        $this->assertLessThan(
            50.0,
            $durationMakespanMs,
            sprintf('MinMakespanOptimizer took %.2f ms, expected < 50 ms', $durationMakespanMs)
        );
    }
}

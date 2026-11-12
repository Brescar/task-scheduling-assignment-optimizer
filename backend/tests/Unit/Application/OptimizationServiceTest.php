<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application;

use App\Application\Service\JsonDatasetLoader;
use App\Application\Service\OptimizationService;
use PHPUnit\Framework\TestCase;

/**
 * Unit test suite for OptimizationService.
 * Verifies supported variant queries, exception handling on invalid variants,
 * and payload-driven execution.
 */
final class OptimizationServiceTest extends TestCase
{
    private OptimizationService $service;

    protected function setUp(): void
    {
        $this->service = new OptimizationService(new JsonDatasetLoader());
    }

    /**
     * Verifies that the service advertises both required optimization variants: 'cost' and 'makespan'.
     */
    public function testSupportedVariants(): void
    {
        // Act
        $variants = $this->service->getSupportedVariants();

        // Assert
        $this->assertContains('cost', $variants);
        $this->assertContains('makespan', $variants);
    }

    /**
     * Verifies that requesting an unsupported variant throws an InvalidArgumentException.
     */
    public function testInvalidVariantThrowsException(): void
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid optimization variant "unknown"');

        // Act
        $this->service->run([], [], 'unknown');
    }

    /**
     * Verifies that optimization executes successfully from a raw in-memory payload array.
     */
    public function testRunFromPayload(): void
    {
        // Arrange
        $payload = [
            'employees' => [
                ['employeeId' => 1, 'skillLevel' => 2, 'hourlyRate' => 100.0],
            ],
            'tasks' => [
                ['taskId' => 1, 'skillLevel' => 1, 'estimation' => 3.0],
            ],
        ];

        // Act
        $result = $this->service->runFromPayload($payload, 'cost');

        // Assert
        $this->assertTrue($result->isFeasible());
        $this->assertSame(300.0, $result->totalPrice);
        $this->assertSame(3.0, $result->makespan);
    }
}

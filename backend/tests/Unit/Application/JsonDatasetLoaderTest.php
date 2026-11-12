<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application;

use App\Application\Service\JsonDatasetLoader;
use PHPUnit\Framework\TestCase;

/**
 * Unit test suite for JsonDatasetLoader.
 * Verifies JSON deserialization, array parsing, schema validation, and missing-file handling.
 */
final class JsonDatasetLoaderTest extends TestCase
{
    private JsonDatasetLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new JsonDatasetLoader();
    }

    /**
     * Verifies parsing valid employee JSON into domain Employee entities.
     */
    public function testLoadValidEmployees(): void
    {
        // Arrange
        $json = '[{"employeeId": 1, "skillLevel": 2, "hourlyRate": 150.0}]';

        // Act
        $employees = $this->loader->parseEmployeesJson($json);

        // Assert
        $this->assertCount(1, $employees);
        $this->assertSame(1, $employees[0]->id);
        $this->assertSame(2, $employees[0]->skillLevel);
        $this->assertSame(150.0, $employees[0]->hourlyRate);
    }

    /**
     * Verifies parsing valid task JSON into domain Task entities.
     */
    public function testLoadValidTasks(): void
    {
        // Arrange
        $json = '[{"taskId": 5, "skillLevel": 3, "estimation": 4.5}]';

        // Act
        $tasks = $this->loader->parseTasksJson($json);

        // Assert
        $this->assertCount(1, $tasks);
        $this->assertSame(5, $tasks[0]->id);
        $this->assertSame(3, $tasks[0]->skillLevel);
        $this->assertSame(4.5, $tasks[0]->estimation);
    }

    /**
     * Verifies that malformed JSON triggers an InvalidArgumentException.
     */
    public function testInvalidJsonThrowsException(): void
    {
        // Arrange
        $invalidJson = '{not valid json}';

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Malformed JSON');

        // Act
        $this->loader->parseEmployeesJson($invalidJson);
    }

    /**
     * Verifies that payload objects missing required fields trigger an InvalidArgumentException.
     */
    public function testMissingFieldThrowsException(): void
    {
        // Arrange
        $incompletePayload = '[{"employeeId": 1, "hourlyRate": 100}]';

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('missing required fields');

        // Act
        $this->loader->parseEmployeesJson($incompletePayload);
    }

    /**
     * Verifies that attempting to read a non-existent file triggers an InvalidArgumentException.
     */
    public function testFileNotFoundThrowsException(): void
    {
        // Arrange
        $missingPath = '/non/existent/path/employees.json';

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('file not found');

        // Act
        $this->loader->loadEmployeesFromFile($missingPath);
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Model\Employee;
use App\Domain\Model\Task;

/**
 * Service responsible for validating and loading datasets from JSON files, raw strings, or arrays.
 */
final class JsonDatasetLoader
{
    /**
     * Loads and validates employees from a JSON file.
     *
     * @return Employee[]
     */
    public function loadEmployeesFromFile(string $filePath): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \InvalidArgumentException(sprintf('Employees file not found or not readable: "%s".', $filePath));
        }

        $content = (string) file_get_contents($filePath);

        return $this->parseEmployeesJson($content);
    }

    /**
     * Loads and validates tasks from a JSON file.
     *
     * @return Task[]
     */
    public function loadTasksFromFile(string $filePath): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \InvalidArgumentException(sprintf('Tasks file not found or not readable: "%s".', $filePath));
        }

        $content = (string) file_get_contents($filePath);

        return $this->parseTasksJson($content);
    }

    /**
     * Parses and validates a raw JSON string into domain Employee models.
     *
     * @return Employee[]
     */
    public function parseEmployeesJson(string $json): array
    {
        $cleanJson = preg_replace('/^\xEF\xBB\xBF/', '', trim($json)) ?? trim($json);

        try {
            $data = json_decode($cleanJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException(sprintf('Malformed JSON in employees data: %s', $e->getMessage()), 0, $e);
        }

        if (!is_array($data)) {
            throw new \InvalidArgumentException('Employees data must be a JSON array of objects.');
        }

        return $this->parseEmployeesArray($data);
    }

    /**
     * Validates and converts an array of employee data structures into domain Employee models.
     *
     * @param array<mixed> $data
     * @return Employee[]
     */
    public function parseEmployeesArray(array $data): array
    {
        $employees = [];
        foreach ($data as $index => $item) {
            if (!is_array($item)) {
                throw new \InvalidArgumentException(sprintf('Employee at index %d is not a valid JSON object.', $index));
            }

            if (!isset($item['employeeId'], $item['skillLevel'], $item['hourlyRate'])) {
                throw new \InvalidArgumentException(sprintf(
                    'Employee at index %d missing required fields (employeeId, skillLevel, hourlyRate).',
                    $index
                ));
            }

            $employees[] = new Employee(
                id: (int) $item['employeeId'],
                skillLevel: (int) $item['skillLevel'],
                hourlyRate: (float) $item['hourlyRate'],
            );
        }

        return $employees;
    }

    /**
     * Parses and validates a raw JSON string into domain Task models.
     *
     * @return Task[]
     */
    public function parseTasksJson(string $json): array
    {
        $cleanJson = preg_replace('/^\xEF\xBB\xBF/', '', trim($json)) ?? trim($json);

        try {
            $data = json_decode($cleanJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException(sprintf('Malformed JSON in tasks data: %s', $e->getMessage()), 0, $e);
        }

        if (!is_array($data)) {
            throw new \InvalidArgumentException('Tasks data must be a JSON array of objects.');
        }

        return $this->parseTasksArray($data);
    }

    /**
     * Validates and converts an array of task data structures into domain Task models.
     *
     * @param array<mixed> $data
     * @return Task[]
     */
    public function parseTasksArray(array $data): array
    {
        $tasks = [];
        foreach ($data as $index => $item) {
            if (!is_array($item)) {
                throw new \InvalidArgumentException(sprintf('Task at index %d is not a valid JSON object.', $index));
            }

            if (!isset($item['taskId'], $item['skillLevel'], $item['estimation'])) {
                throw new \InvalidArgumentException(sprintf(
                    'Task at index %d missing required fields (taskId, skillLevel, estimation).',
                    $index
                ));
            }

            $tasks[] = new Task(
                id: (int) $item['taskId'],
                skillLevel: (int) $item['skillLevel'],
                estimation: (float) $item['estimation'],
            );
        }

        return $tasks;
    }
}

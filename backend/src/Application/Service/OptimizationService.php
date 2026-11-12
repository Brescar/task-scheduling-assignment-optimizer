<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Contract\OptimizerInterface;
use App\Domain\Model\OptimizationResult;
use App\Domain\Optimizer\MinCostOptimizer;
use App\Domain\Optimizer\MinMakespanOptimizer;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * Application service coordinating optimization execution across variants and data sources.
 */
final class OptimizationService
{
    /** @var array<string, OptimizerInterface> */
    private array $optimizers = [];

    /**
     * @param iterable<OptimizerInterface>|null $optimizers
     */
    public function __construct(
        private readonly JsonDatasetLoader $datasetLoader,
        #[TaggedIterator('app.optimizer')]
        ?iterable $optimizers = null,
    ) {
        $resolved = [];
        if ($optimizers !== null) {
            foreach ($optimizers as $optimizer) {
                if ($optimizer instanceof OptimizerInterface) {
                    $resolved[$optimizer->getVariant()] = $optimizer;
                }
            }
        }

        if (empty($resolved)) {
            $cost = new MinCostOptimizer();
            $makespan = new MinMakespanOptimizer();
            $resolved[$cost->getVariant()] = $cost;
            $resolved[$makespan->getVariant()] = $makespan;
        }

        $this->optimizers = $resolved;
    }

    /**
     * Executes optimization for the specified variant on provided domain entities.
     *
     * @param \App\Domain\Model\Employee[] $employees
     * @param \App\Domain\Model\Task[] $tasks
     */
    public function run(array $employees, array $tasks, string $variant): OptimizationResult
    {
        $normalizedVariant = strtolower(trim($variant));

        if (!isset($this->optimizers[$normalizedVariant])) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid optimization variant "%s". Supported variants are: %s',
                $variant,
                implode(', ', array_keys($this->optimizers))
            ));
        }

        return $this->optimizers[$normalizedVariant]->optimize($employees, $tasks);
    }

    /**
     * Loads datasets from file paths and executes optimization.
     */
    public function runFromFiles(string $employeesPath, string $tasksPath, string $variant): OptimizationResult
    {
        $employees = $this->datasetLoader->loadEmployeesFromFile($employeesPath);
        $tasks = $this->datasetLoader->loadTasksFromFile($tasksPath);

        return $this->run($employees, $tasks, $variant);
    }

    /**
     * Parses payload array and executes optimization.
     *
     * @param array{employees?: mixed, tasks?: mixed} $payload
     */
    public function runFromPayload(array $payload, string $variant): OptimizationResult
    {
        if (!isset($payload['employees']) || !is_array($payload['employees'])) {
            throw new \InvalidArgumentException('Payload must contain an "employees" array.');
        }

        if (!isset($payload['tasks']) || !is_array($payload['tasks'])) {
            throw new \InvalidArgumentException('Payload must contain a "tasks" array.');
        }

        $employees = $this->datasetLoader->parseEmployeesArray($payload['employees']);
        $tasks = $this->datasetLoader->parseTasksArray($payload['tasks']);

        return $this->run($employees, $tasks, $variant);
    }

    /**
     * @return string[]
     */
    public function getSupportedVariants(): array
    {
        return array_keys($this->optimizers);
    }
}

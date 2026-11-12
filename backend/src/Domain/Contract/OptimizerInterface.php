<?php

declare(strict_types=1);

namespace App\Domain\Contract;

use App\Domain\Model\Employee;
use App\Domain\Model\OptimizationResult;
use App\Domain\Model\Task;

/**
 * Strategy interface for task scheduling optimizers.
 */
interface OptimizerInterface
{
    /**
     * Executes the optimization algorithm on the given employees and tasks.
     *
     * @param Employee[] $employees
     * @param Task[] $tasks
     */
    public function optimize(array $employees, array $tasks): OptimizationResult;

    /**
     * Returns the machine-readable name of the variant (e.g. 'cost' or 'makespan').
     */
    public function getVariant(): string;
}


<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Immutable domain result representing the outcome of an optimization run.
 */
final readonly class OptimizationResult implements \JsonSerializable
{
    public const STATUS_FEASIBLE = 'feasible';
    public const STATUS_INFEASIBLE = 'infeasible';

    public const VARIANT_COST = 'cost';
    public const VARIANT_MAKESPAN = 'makespan';

    /**
     * @param Assignment[] $assignments
     * @param EmployeeWorkload[] $employeeSummaries
     * @param int[] $unassignableTaskIds
     */
    public function __construct(
        public string $status,
        public string $variant,
        public array $assignments = [],
        public float $totalPrice = 0.0,
        public float $makespan = 0.0,
        public array $employeeSummaries = [],
        public array $unassignableTaskIds = [],
    ) {}

    /**
     * Factory method for an infeasible result.
     *
     * @param int[] $unassignableTaskIds
     */
    public static function infeasible(string $variant, array $unassignableTaskIds): self
    {
        $uniqueIds = array_values(array_unique($unassignableTaskIds));
        sort($uniqueIds);

        return new self(
            status: self::STATUS_INFEASIBLE,
            variant: $variant,
            assignments: [],
            totalPrice: 0.0,
            makespan: 0.0,
            employeeSummaries: [],
            unassignableTaskIds: $uniqueIds,
        );
    }

    public function isFeasible(): bool
    {
        return $this->status === self::STATUS_FEASIBLE;
    }

    public function jsonSerialize(): array
    {
        if (!$this->isFeasible()) {
            return [
                'status' => $this->status,
                'variant' => $this->variant,
                'unassignableTaskIds' => $this->unassignableTaskIds,
                'message' => sprintf(
                    'Optimization infeasible. No eligible employee available for task ID(s): %s',
                    implode(', ', $this->unassignableTaskIds)
                ),
            ];
        }

        $activeEmployees = count(array_filter(
            $this->employeeSummaries,
            static fn(EmployeeWorkload $summary): bool => $summary->totalAssignedHours > 0
        ));

        return [
            'status' => $this->status,
            'variant' => $this->variant,
            'totalPrice' => $this->totalPrice,
            'makespan' => $this->makespan,
            'taskCount' => count($this->assignments),
            'activeEmployeeCount' => $activeEmployees,
            'totalEmployeeCount' => count($this->employeeSummaries),
            'assignments' => array_map(
                static fn(Assignment $a): array => $a->jsonSerialize(),
                $this->assignments
            ),
            'employeeSummaries' => array_map(
                static fn(EmployeeWorkload $w): array => $w->jsonSerialize(),
                $this->employeeSummaries
            ),
        ];
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Command;

use App\Application\Service\DataPathResolver;
use App\Application\Service\OptimizationService;
use App\Domain\Model\Assignment;
use App\Domain\Model\EmployeeWorkload;
use App\Domain\Model\OptimizationResult;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:optimize',
    description: 'Calculates the optimal task-employee assignment for Minimum Cost or Minimum Makespan.'
)]
final class OptimizeCommand extends Command
{
    public function __construct(
        private readonly OptimizationService $optimizationService,
        private readonly DataPathResolver $dataPathResolver,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'employeesFile',
                InputArgument::OPTIONAL,
                'Path to employees JSON file'
            )
            ->addArgument(
                'tasksFile',
                InputArgument::OPTIONAL,
                'Path to tasks JSON file'
            )
            ->addOption(
                'variant',
                null,
                InputOption::VALUE_REQUIRED,
                'Optimization variant: "cost" (Minimum Total Cost) or "makespan" (Minimum Completion Time)',
                'cost'
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Output format: "table" (human-readable) or "json" (standard machine payload)',
                'table'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $variant = (string) $input->getOption('variant');
        $format = (string) $input->getOption('format');

        // Resolve file paths (with sensible project root defaults)
        $employeesFile = (string) ($input->getArgument('employeesFile') ?? $this->dataPathResolver->resolve('employees.json'));
        $tasksFile = (string) ($input->getArgument('tasksFile') ?? $this->dataPathResolver->resolve('tasks.json'));

        try {
            $result = $this->optimizationService->runFromFiles($employeesFile, $tasksFile, $variant);
        } catch (\Throwable $e) {
            if ($format === 'json') {
                $output->writeln((string) json_encode([
                    'error' => $e->getMessage(),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            } else {
                $io->error(sprintf('Optimization failed: %s', $e->getMessage()));
            }

            return Command::FAILURE;
        }

        if ($format === 'json') {
            $output->writeln((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return $result->isFeasible() ? Command::SUCCESS : Command::FAILURE;
        }

        // Render human-readable console tables
        $this->renderTableOutput($io, $result, $employeesFile, $tasksFile);

        return $result->isFeasible() ? Command::SUCCESS : Command::FAILURE;
    }

    private function renderTableOutput(
        SymfonyStyle $io,
        OptimizationResult $result,
        string $employeesFile,
        string $tasksFile
    ): void {
        $io->title('Beijer Ref - Task Scheduling & Employee Assignment Engine');
        $io->text(sprintf('Input files: Employees [%s] | Tasks [%s]', $employeesFile, $tasksFile));

        if (!$result->isFeasible()) {
            $io->warning(sprintf(
                'Problem instance is INFEASIBLE for variant "%s". No eligible employee found for task ID(s): %s',
                strtoupper($result->variant),
                implode(', ', $result->unassignableTaskIds)
            ));
            return;
        }

        // Metrics Summary Card
        $activeEmployees = count(array_filter(
            $result->employeeSummaries,
            static fn(EmployeeWorkload $w): bool => $w->totalAssignedHours > 0
        ));

        $io->section(sprintf('Optimization Summary (%s)', strtoupper($result->variant)));
        $io->definitionList(
            ['Variant' => $result->variant === 'cost' ? 'Minimum Total Cost' : 'Minimum Makespan'],
            ['Status' => 'FEASIBLE'],
            ['Total Cost' => sprintf('$%.2f', $result->totalPrice)],
            ['Makespan (Completion Time)' => sprintf('%.2f hours', $result->makespan)],
            ['Total Tasks Assigned' => (string) count($result->assignments)],
            ['Active / Total Employees' => sprintf('%d / %d', $activeEmployees, count($result->employeeSummaries))],
        );

        // Assignments Table
        $io->section('Task Assignments');
        $assignmentRows = array_map(
            static fn(Assignment $a): array => [
                $a->task->id,
                $a->task->skillLevel,
                sprintf('%.1f h', $a->task->estimation),
                $a->employee->id,
                $a->employee->skillLevel,
                sprintf('$%.2f/h', $a->employee->hourlyRate),
                sprintf('$%.2f', $a->cost),
            ],
            $result->assignments
        );

        $io->table(
            ['Task ID', 'Required Skill', 'Duration', 'Employee ID', 'Employee Skill', 'Hourly Rate', 'Cost'],
            $assignmentRows
        );

        // Employee Workloads Table
        $io->section('Employee Workload Breakdown');
        $workloadRows = array_map(
            static function (EmployeeWorkload $w) use ($result): array {
                $pct = $result->makespan > 0 ? ($w->totalAssignedHours / $result->makespan) * 100 : 0;
                $barLength = (int) round($pct / 5);
                $bar = str_repeat('█', $barLength) . str_repeat('░', 20 - $barLength);
                $isBottleneck = $w->totalAssignedHours > 0 && abs($w->totalAssignedHours - $result->makespan) < 0.001;

                return [
                    $w->employeeId,
                    sprintf('%.2f h', $w->totalAssignedHours),
                    count($w->assignedTasks),
                    sprintf('[%s] %5.1f%% %s', $bar, $pct, $isBottleneck ? '<fg=red;options=bold>BOTTLENECK</>' : ''),
                ];
            },
            $result->employeeSummaries
        );

        $io->table(
            ['Employee ID', 'Total Assigned Hours', 'Tasks Count', 'Relative Load (vs Makespan)'],
            $workloadRows
        );
    }
}


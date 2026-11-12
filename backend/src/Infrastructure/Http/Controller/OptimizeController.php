<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Service\DataPathResolver;
use App\Application\Service\OptimizationService;
use App\Infrastructure\Http\Dto\OptimizeQueryDto;
use App\Infrastructure\Http\Dto\OptimizeRequestDto;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class OptimizeController
{
    public function __construct(
        private readonly OptimizationService $optimizationService,
        private readonly DataPathResolver $dataPathResolver,
    ) {}

    #[Route('/api/v1/optimize', name: 'api_v1_optimize_post', methods: ['POST'])]
    public function optimizePost(
        #[MapQueryString] ?OptimizeQueryDto $query = null,
        #[MapRequestPayload] ?OptimizeRequestDto $payload = null,
    ): JsonResponse {
        $variant = $query?->variant ?? 'cost';

        try {
            if ($payload === null || $payload->isEmpty()) {
                $employeesFile = $this->dataPathResolver->resolve('employees.json');
                $tasksFile = $this->dataPathResolver->resolve('tasks.json');
                $result = $this->optimizationService->runFromFiles($employeesFile, $tasksFile, $variant);
            } else {
                $result = $this->optimizationService->runFromPayload($payload->toPayloadArray(), $variant);
            }

            return new JsonResponse($result, Response::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'An unexpected server error occurred: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/v1/optimize', name: 'api_v1_optimize_get', methods: ['GET'])]
    public function optimizeGet(
        #[MapQueryString] ?OptimizeQueryDto $query = null,
    ): JsonResponse {
        $variant = $query?->variant ?? 'cost';

        try {
            $employeesFile = $this->dataPathResolver->resolve('employees.json');
            $tasksFile = $this->dataPathResolver->resolve('tasks.json');

            $result = $this->optimizationService->runFromFiles($employeesFile, $tasksFile, $variant);

            return new JsonResponse($result, Response::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'An unexpected server error occurred: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Integration test suite for OptimizeController and REST API endpoints.
 * Verifies HTTP routing, Symfony request/query DTO attribute mapping,
 * JSON error handling, and end-to-end optimization execution.
 */
final class OptimizeControllerTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new Kernel('test', true);
        $this->kernel->boot();
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
    }

    /**
     * Verifies the health check endpoint returns 200 OK with runtime metadata.
     */
    public function testHealthEndpoint(): void
    {
        // Arrange
        $request = Request::create('/api/v1/health', 'GET');

        // Act
        $response = $this->kernel->handle($request);

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('ok', $data['status']);
        $this->assertArrayHasKey('php', $data);
    }

    /**
     * Verifies GET /api/v1/optimize?variant=cost executes optimization from default JSON files.
     */
    public function testOptimizeGetCost(): void
    {
        // Arrange
        $request = Request::create('/api/v1/optimize', 'GET', ['variant' => 'cost']);

        // Act
        $response = $this->kernel->handle($request);

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('feasible', $data['status']);
        $this->assertSame('cost', $data['variant']);
        $this->assertSame(17460.0, (float) $data['totalPrice']);
        $this->assertSame(51.0, (float) $data['makespan']);
        $this->assertCount(30, $data['assignments']);
    }

    /**
     * Verifies POST /api/v1/optimize?variant=makespan executes optimization with a custom JSON request body.
     */
    public function testOptimizePostCustomPayload(): void
    {
        // Arrange
        $payload = [
            'employees' => [
                ['employeeId' => 1, 'skillLevel' => 1, 'hourlyRate' => 50.0],
                ['employeeId' => 2, 'skillLevel' => 2, 'hourlyRate' => 100.0],
            ],
            'tasks' => [
                ['taskId' => 10, 'skillLevel' => 1, 'estimation' => 2.0],
                ['taskId' => 20, 'skillLevel' => 2, 'estimation' => 4.0],
            ],
        ];

        $request = Request::create(
            '/api/v1/optimize?variant=makespan',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        // Act
        $response = $this->kernel->handle($request);

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('feasible', $data['status']);
        $this->assertSame('makespan', $data['variant']);
        $this->assertCount(2, $data['assignments']);
    }

    /**
     * Verifies that sending malformed JSON returns a 400 Bad Request with an error description.
     */
    public function testOptimizePostInvalidJsonReturnsBadRequest(): void
    {
        // Arrange
        $request = Request::create(
            '/api/v1/optimize',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{invalid-json}'
        );

        // Act
        $response = $this->kernel->handle($request);

        // Assert
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Malformed JSON', $data['error']);
    }

    /**
     * Verifies that an infeasible scenario returns HTTP 200 with status="infeasible"
     * and lists the unassignable task IDs.
     */
    public function testOptimizeInfeasibleReturnsFeasibleStatusInfeasible(): void
    {
        // Arrange
        $payload = [
            'employees' => [
                ['employeeId' => 1, 'skillLevel' => 1, 'hourlyRate' => 50.0],
            ],
            'tasks' => [
                ['taskId' => 99, 'skillLevel' => 3, 'estimation' => 2.0],
            ],
        ];

        $request = Request::create(
            '/api/v1/optimize',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        // Act
        $response = $this->kernel->handle($request);

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('infeasible', $data['status']);
        $this->assertSame([99], $data['unassignableTaskIds']);
    }
}

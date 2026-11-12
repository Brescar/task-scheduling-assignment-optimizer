<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Immutable domain entity representing a task.
 */
final readonly class Task implements \JsonSerializable
{
    public function __construct(
        public int $id,
        public int $skillLevel,
        public float $estimation,
    ) {
        if ($this->id <= 0) {
            throw new \InvalidArgumentException(sprintf('Task ID must be a positive integer, got %d.', $this->id));
        }

        if ($this->skillLevel < 1) {
            throw new \InvalidArgumentException(sprintf('Task skill level must be at least 1, got %d.', $this->skillLevel));
        }

        if ($this->estimation <= 0.0) {
            throw new \InvalidArgumentException(sprintf('Task estimation must be greater than zero, got %.2f.', $this->estimation));
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'taskId' => $this->id,
            'skillLevel' => $this->skillLevel,
            'estimation' => $this->estimation,
        ];
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}

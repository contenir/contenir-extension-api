<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

/**
 * Records by section and id, with snapshots standing in for a transaction.
 *
 * @internal
 */
final class Records
{
    /**
     * @var array<string, array<string|int, array<string, mixed>>>
     */
    private array $rows = [];

    /**
     * @var array<string, int>
     */
    private array $last = [];

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $section, string|int $id): ?array
    {
        return $this->rows[$section][$id] ?? null;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function put(string $section, string|int $id, array $values): void
    {
        $this->rows[$section][$id] = $values;
    }

    /**
     * @param array{array<string, array<string|int, array<string, mixed>>>, array<string, int>} $snapshot
     */
    public function restore(array $snapshot): void
    {
        [$this->rows, $this->last] = $snapshot;
    }

    /**
     * @return array{array<string, array<string|int, array<string, mixed>>>, array<string, int>}
     */
    public function snapshot(): array
    {
        return [$this->rows, $this->last];
    }

    /**
     * Writes a record, giving a new one the next id in its section.
     *
     * @param array<string, mixed> $values
     */
    public function write(string $section, string|int|null $id, array $values): string|int
    {
        if (null === $id) {
            $id                   = ($this->last[$section] ?? 0) + 1;
            $this->last[$section] = $id;
        }

        $this->put($section, $id, $values);

        return $id;
    }
}

<?php

namespace App\Tools\WorkorderTools;

use Illuminate\Support\Collection;

class WorkorderToolRegistry
{
    /**
     * Add future workorder tools here. Each tool owns its metadata and inputs.
     *
     * @var array<int, class-string<WorkorderToolDefinition>>
     */
    private array $toolClasses = [
        NlgErj170Sleeve37Tool::class,
    ];

    /**
     * @return Collection<int, WorkorderToolDefinition>
     */
    public function definitions(): Collection
    {
        return collect($this->toolClasses)
            ->map(fn (string $class) => app($class));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->definitions()
            ->map(fn (WorkorderToolDefinition $tool) => $tool->toArray())
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forManualNumber(?string $manualNumber): array
    {
        if (!$manualNumber) {
            return $this->all();
        }

        return $this->definitions()
            ->filter(fn (WorkorderToolDefinition $tool) => in_array($manualNumber, $tool->manualNumbers(), true))
            ->map(fn (WorkorderToolDefinition $tool) => $tool->toArray())
            ->values()
            ->all();
    }

    public function find(string $key): ?array
    {
        $definition = $this->definitions()
            ->first(fn (WorkorderToolDefinition $tool) => $tool->key() === $key);

        return $definition ? $definition->toArray() : null;
    }
}

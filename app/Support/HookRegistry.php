<?php

declare(strict_types=1);

namespace App\Support;

/**
 * WordPress-style Action & Filter hook engine (SRS section IV.1.a).
 * Registered as a singleton; use the global helpers in app/helpers.php
 * (do_action, apply_filters, add_action, add_filter) rather than resolving
 * this class directly, to match the SRS's documented hook API.
 */
class HookRegistry
{
    /** @var array<string, array<int, array<int, callable>>> */
    private array $actions = [];

    /** @var array<string, array<int, array<int, callable>>> */
    private array $filters = [];

    public function addAction(string $hook, callable $callback, int $priority = 10): void
    {
        $this->actions[$hook][$priority][] = $callback;
    }

    public function doAction(string $hook, mixed ...$args): void
    {
        foreach ($this->ordered($this->actions[$hook] ?? []) as $callback) {
            $callback(...$args);
        }
    }

    public function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        $this->filters[$hook][$priority][] = $callback;
    }

    public function applyFilters(string $hook, mixed $value, mixed ...$args): mixed
    {
        foreach ($this->ordered($this->filters[$hook] ?? []) as $callback) {
            $value = $callback($value, ...$args);
        }

        return $value;
    }

    /**
     * @param  array<int, array<int, callable>>  $callbacksByPriority
     * @return array<int, callable>
     */
    private function ordered(array $callbacksByPriority): array
    {
        ksort($callbacksByPriority);

        return array_merge(...array_values($callbacksByPriority ?: [[]]));
    }
}

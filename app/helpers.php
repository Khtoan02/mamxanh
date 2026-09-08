<?php

declare(strict_types=1);

use App\Support\HookRegistry;
use App\Support\PostTypeRegistry;
use App\Support\TaxonomyRegistry;

if (! function_exists('add_action')) {
    function add_action(string $hook, callable $callback, int $priority = 10): void
    {
        app(HookRegistry::class)->addAction($hook, $callback, $priority);
    }
}

if (! function_exists('do_action')) {
    function do_action(string $hook, mixed ...$args): void
    {
        app(HookRegistry::class)->doAction($hook, ...$args);
    }
}

if (! function_exists('add_filter')) {
    function add_filter(string $hook, callable $callback, int $priority = 10): void
    {
        app(HookRegistry::class)->addFilter($hook, $callback, $priority);
    }
}

if (! function_exists('apply_filters')) {
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
    {
        return app(HookRegistry::class)->applyFilters($hook, $value, ...$args);
    }
}

if (! function_exists('register_post_type')) {
    function register_post_type(string $slug, array $args = []): void
    {
        app(PostTypeRegistry::class)->register($slug, $args);
    }
}

if (! function_exists('get_post_type')) {
    function get_post_type(string $slug): ?array
    {
        return app(PostTypeRegistry::class)->get($slug);
    }
}

if (! function_exists('get_post_types')) {
    function get_post_types(): array
    {
        return app(PostTypeRegistry::class)->all();
    }
}

if (! function_exists('register_taxonomy')) {
    function register_taxonomy(string $slug, array $postTypes, array $args = []): void
    {
        app(TaxonomyRegistry::class)->register($slug, $postTypes, $args);
    }
}

if (! function_exists('get_taxonomies_for_post_type')) {
    function get_taxonomies_for_post_type(string $postType): array
    {
        return app(TaxonomyRegistry::class)->forPostType($postType);
    }
}

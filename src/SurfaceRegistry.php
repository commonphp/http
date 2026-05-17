<?php

declare(strict_types=1);

namespace CommonPHP\HTTP;

use CommonPHP\HTTP\Contracts\HttpSurfaceInterface;
use CommonPHP\HTTP\Exceptions\SurfaceNotFoundException;
use InvalidArgumentException;

class SurfaceRegistry
{
    /**
     * @var array<string, array{name: string, surface: HttpSurfaceInterface, prefix: string, priority: int}>
     */
    private array $surfaces = [];

    public function register(
        string $name,
        HttpSurfaceInterface $surface,
        string $pathPrefix = '/',
        int $priority = 0,
    ): static {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('HTTP surface name cannot be empty.');
        }

        $this->surfaces[$name] = [
            'name' => $name,
            'surface' => $surface,
            'prefix' => $this->normalizePrefix($pathPrefix),
            'priority' => $priority,
        ];

        $this->sort();

        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->surfaces[$name]);
    }

    public function get(string $name): HttpSurfaceInterface
    {
        return $this->surfaces[$name]['surface']
            ?? throw SurfaceNotFoundException::forName($name);
    }

    public function remove(string $name): static
    {
        unset($this->surfaces[$name]);

        return $this;
    }

    public function find(Request $request): ?HttpSurfaceInterface
    {
        foreach ($this->surfaces as $entry) {
            if (!$this->matchesPrefix($request->path(), $entry['prefix'])) {
                continue;
            }

            if ($entry['surface']->supports($request)) {
                return $entry['surface'];
            }
        }

        return null;
    }

    /**
     * @return array<string, HttpSurfaceInterface>
     */
    public function all(): array
    {
        $surfaces = [];

        foreach ($this->surfaces as $name => $entry) {
            $surfaces[$name] = $entry['surface'];
        }

        return $surfaces;
    }

    /**
     * @return array<string, array{name: string, prefix: string, priority: int}>
     */
    public function entries(): array
    {
        $entries = [];

        foreach ($this->surfaces as $name => $entry) {
            $entries[$name] = [
                'name' => $entry['name'],
                'prefix' => $entry['prefix'],
                'priority' => $entry['priority'],
            ];
        }

        return $entries;
    }

    private function normalizePrefix(string $prefix): string
    {
        $prefix = trim($prefix);

        if ($prefix === '' || $prefix === '/') {
            return '/';
        }

        $prefix = '/' . ltrim($prefix, '/');

        return rtrim($prefix, '/');
    }

    private function matchesPrefix(string $path, string $prefix): bool
    {
        return $prefix === '/'
            || $path === $prefix
            || str_starts_with($path, $prefix . '/');
    }

    private function sort(): void
    {
        uasort($this->surfaces, static function (array $left, array $right): int {
            $priority = $right['priority'] <=> $left['priority'];

            if ($priority !== 0) {
                return $priority;
            }

            return strlen($right['prefix']) <=> strlen($left['prefix']);
        });
    }
}

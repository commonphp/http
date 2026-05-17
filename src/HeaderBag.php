<?php

declare(strict_types=1);

namespace CommonPHP\HTTP;

use CommonPHP\HTTP\Exceptions\InvalidHeaderException;
use Countable;
use Stringable;

class HeaderBag implements Countable
{
    /**
     * @var array<string, array{name: string, values: list<string>}>
     */
    private array $headers = [];

    /**
     * @param array<string, mixed> $headers
     */
    public function __construct(array $headers = [])
    {
        foreach ($headers as $name => $values) {
            $this->set((string) $name, $values);
        }
    }

    /**
     * @param array<string, mixed> $server
     */
    public static function fromServer(array $server): self
    {
        $headers = [];

        foreach ($server as $name => $value) {
            if (!is_scalar($value) && !$value instanceof Stringable) {
                continue;
            }

            if (str_starts_with((string) $name, 'HTTP_')) {
                $header = substr((string) $name, 5);
            } elseif (in_array($name, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $header = (string) $name;
            } else {
                continue;
            }

            $headers[self::serverNameToHeaderName($header)] = (string) $value;
        }

        return new self($headers);
    }

    private static function serverNameToHeaderName(string $name): string
    {
        return str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $name))));
    }

    public function count(): int
    {
        return count($this->headers);
    }

    public function has(string $name): bool
    {
        return isset($this->headers[$this->normalizeName($name)]);
    }

    public function get(string $name, ?string $default = null): ?string
    {
        $values = $this->values($name);

        return $values === [] ? $default : implode(', ', $values);
    }

    public function first(string $name, ?string $default = null): ?string
    {
        $values = $this->values($name);

        return $values[0] ?? $default;
    }

    /**
     * @return list<string>
     */
    public function values(string $name): array
    {
        return $this->headers[$this->normalizeName($name)]['values'] ?? [];
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_map(
            static fn (array $header): string => $header['name'],
            array_values($this->headers),
        );
    }

    /**
     * @return array<string, list<string>>
     */
    public function all(): array
    {
        $headers = [];

        foreach ($this->headers as $header) {
            $headers[$header['name']] = $header['values'];
        }

        return $headers;
    }

    public function set(string $name, string|array $values): static
    {
        $this->assertValidName($name);

        $this->headers[$this->normalizeName($name)] = [
            'name' => $this->canonicalName($name),
            'values' => $this->normalizeValues($name, $values),
        ];

        return $this;
    }

    public function add(string $name, string|array $values): static
    {
        $this->assertValidName($name);

        $normalized = $this->normalizeName($name);
        $values = $this->normalizeValues($name, $values);

        if (!isset($this->headers[$normalized])) {
            return $this->set($name, $values);
        }

        array_push($this->headers[$normalized]['values'], ...$values);

        return $this;
    }

    public function remove(string $name): static
    {
        unset($this->headers[$this->normalizeName($name)]);

        return $this;
    }

    public function with(string $name, string|array $values): self
    {
        $clone = clone $this;
        $clone->set($name, $values);

        return $clone;
    }

    public function withAdded(string $name, string|array $values): self
    {
        $clone = clone $this;
        $clone->add($name, $values);

        return $clone;
    }

    public function without(string $name): self
    {
        $clone = clone $this;
        $clone->remove($name);

        return $clone;
    }

    /**
     * @param array<string, mixed>|self $headers
     */
    public function merge(array|self $headers): static
    {
        $headers = $headers instanceof self ? $headers->all() : $headers;

        foreach ($headers as $name => $values) {
            $this->set((string) $name, $values);
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->headers === [];
    }

    private function normalizeName(string $name): string
    {
        return strtolower($name);
    }

    private function canonicalName(string $name): string
    {
        return implode('-', array_map(
            static fn (string $part): string => ucfirst(strtolower($part)),
            explode('-', $name),
        ));
    }

    /**
     * @return list<string>
     */
    private function normalizeValues(string $name, string|array $values): array
    {
        $values = is_array($values) ? array_values($values) : [$values];

        if ($values === []) {
            throw InvalidHeaderException::forValue($name);
        }

        return array_map(function (mixed $value) use ($name): string {
            if (!is_scalar($value) && !$value instanceof Stringable) {
                throw InvalidHeaderException::forValue($name);
            }

            $value = (string) $value;

            if (str_contains($value, "\r") || str_contains($value, "\n")) {
                throw InvalidHeaderException::forValue($name);
            }

            return $value;
        }, $values);
    }

    private function assertValidName(string $name): void
    {
        if ($name === '' || preg_match('/^[A-Za-z0-9!#$%&\'*+.^_`|~-]+$/', $name) !== 1) {
            throw InvalidHeaderException::forName($name);
        }
    }
}

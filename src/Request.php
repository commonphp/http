<?php

declare(strict_types=1);

namespace CommonPHP\HTTP;

use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Enums\RequestScheme;
use CommonPHP\HTTP\Exceptions\InvalidRequestException;
use JsonException;

class Request
{
    private RequestMethod $method;

    private RequestScheme $scheme;

    private string $uri;

    private string $path;

    private string $queryString;

    private HeaderBag $headers;

    private string $body;

    /**
     * @var array<string, mixed>
     */
    private array $queryParams;

    /**
     * @var array<string, mixed>
     */
    private array $cookies;

    /**
     * @var array<string, mixed>
     */
    private array $files;

    /**
     * @var array<string, mixed>
     */
    private array $serverParams;

    /**
     * @var array<string, mixed>
     */
    private array $attributes;

    /**
     * @param array<string, mixed>|HeaderBag $headers
     * @param array<string, mixed> $queryParams
     * @param array<string, mixed> $cookies
     * @param array<string, mixed> $files
     * @param array<string, mixed> $serverParams
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        RequestMethod|string $method = RequestMethod::GET,
        string $uri = '/',
        array|HeaderBag $headers = [],
        string $body = '',
        array $queryParams = [],
        private mixed $parsedBody = null,
        array $cookies = [],
        array $files = [],
        array $serverParams = [],
        RequestScheme|string|null $scheme = null,
        array $attributes = [],
    ) {
        $this->method = $method instanceof RequestMethod ? $method : RequestMethod::fromString($method);
        $this->headers = $headers instanceof HeaderBag ? clone $headers : new HeaderBag($headers);
        $this->body = $body;
        $this->queryParams = $queryParams;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->serverParams = $serverParams;
        $this->attributes = $attributes;
        $this->scheme = $scheme instanceof RequestScheme
            ? $scheme
            : ($scheme === null ? $this->inferScheme($uri, $serverParams) : RequestScheme::fromString($scheme));

        $this->setUri($uri, $queryParams === []);
    }

    public function method(): RequestMethod
    {
        return $this->method;
    }

    public function methodValue(): string
    {
        return $this->method->value();
    }

    public function isMethod(RequestMethod|string $method): bool
    {
        $method = $method instanceof RequestMethod ? $method : RequestMethod::fromString($method);

        return $this->method === $method;
    }

    public function scheme(): RequestScheme
    {
        return $this->scheme;
    }

    public function isSecure(): bool
    {
        return $this->scheme->isSecure();
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function queryString(): string
    {
        return $this->queryString;
    }

    public function target(): string
    {
        return $this->queryString === '' ? $this->path : $this->path . '?' . $this->queryString;
    }

    public function fullUri(): string
    {
        if (parse_url($this->uri, PHP_URL_SCHEME) !== null) {
            return $this->uri;
        }

        $host = $this->host();

        return $host === null ? $this->target() : $this->scheme->value() . '://' . $host . $this->target();
    }

    public function host(): ?string
    {
        $host = $this->headers->first('Host')
            ?? parse_url($this->uri, PHP_URL_HOST)
            ?? ($this->serverParams['HTTP_HOST'] ?? null)
            ?? ($this->serverParams['SERVER_NAME'] ?? null);

        return is_string($host) && $host !== '' ? $host : null;
    }

    public function ip(): ?string
    {
        $ip = $this->serverParams['REMOTE_ADDR'] ?? null;

        return is_string($ip) && $ip !== '' ? $ip : null;
    }

    public function headers(): HeaderBag
    {
        return clone $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return $this->headers->has($name);
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers->get($name, $default);
    }

    public function firstHeader(string $name, ?string $default = null): ?string
    {
        return $this->headers->first($name, $default);
    }

    public function body(): string
    {
        return $this->body;
    }

    public function parsedBody(): mixed
    {
        return $this->parsedBody;
    }

    /**
     * @return array<string, mixed>
     */
    public function queryParams(): array
    {
        return $this->queryParams;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function cookies(): array
    {
        return $this->cookies;
    }

    public function cookie(string $name, mixed $default = null): mixed
    {
        return $this->cookies[$name] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function files(): array
    {
        return $this->files;
    }

    public function file(string $name): mixed
    {
        return $this->files[$name] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function serverParams(): array
    {
        return $this->serverParams;
    }

    public function server(string $name, mixed $default = null): mixed
    {
        return $this->serverParams[$name] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    public function attribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    public function isJson(): bool
    {
        $contentType = strtolower($this->firstHeader('Content-Type', ''));

        return str_contains($contentType, '/json') || str_contains($contentType, '+json');
    }

    public function wantsJson(): bool
    {
        $accept = strtolower($this->firstHeader('Accept', ''));

        return str_contains($accept, '/json') || str_contains($accept, '+json');
    }

    public function accepts(string $contentType): bool
    {
        $accept = strtolower($this->firstHeader('Accept', '*/*'));
        $contentType = strtolower($contentType);
        $type = strstr($contentType, '/', true);

        return $accept === '*/*'
            || str_contains($accept, $contentType)
            || ($type !== false && str_contains($accept, $type . '/*'));
    }

    public function json(bool $associative = true, int $depth = 512): mixed
    {
        try {
            return json_decode($this->body, $associative, $depth, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw InvalidRequestException::because('Invalid JSON body: ' . $exception->getMessage());
        }
    }

    public function withMethod(RequestMethod|string $method): static
    {
        $clone = clone $this;
        $clone->method = $method instanceof RequestMethod ? $method : RequestMethod::fromString($method);

        return $clone;
    }

    public function withUri(string $uri): static
    {
        $clone = clone $this;
        $clone->setUri($uri, true);

        return $clone;
    }

    public function withHeader(string $name, string|array $values): static
    {
        $clone = clone $this;
        $clone->headers = clone $this->headers;
        $clone->headers->set($name, $values);

        return $clone;
    }

    public function withAddedHeader(string $name, string|array $values): static
    {
        $clone = clone $this;
        $clone->headers = clone $this->headers;
        $clone->headers->add($name, $values);

        return $clone;
    }

    public function withoutHeader(string $name): static
    {
        $clone = clone $this;
        $clone->headers = clone $this->headers;
        $clone->headers->remove($name);

        return $clone;
    }

    public function withBody(string $body): static
    {
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }

    public function withParsedBody(mixed $parsedBody): static
    {
        $clone = clone $this;
        $clone->parsedBody = $parsedBody;

        return $clone;
    }

    public function withAttribute(string $name, mixed $value): static
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;

        return $clone;
    }

    public function withoutAttribute(string $name): static
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);

        return $clone;
    }

    private function inferScheme(string $uri, array $serverParams): RequestScheme
    {
        $scheme = parse_url($uri, PHP_URL_SCHEME);

        if (is_string($scheme) && $scheme !== '') {
            return RequestScheme::fromString($scheme);
        }

        return $serverParams === [] ? RequestScheme::HTTP : RequestScheme::fromServer($serverParams);
    }

    private function setUri(string $uri, bool $replaceQueryParams): void
    {
        $parts = parse_url($uri);

        if ($parts === false) {
            throw InvalidRequestException::because('Malformed URI "' . $uri . '".');
        }

        $path = $parts['path'] ?? '/';

        if (!is_string($path) || $path === '') {
            $path = '/';
        }

        $query = $parts['query'] ?? '';

        if (!is_string($query)) {
            $query = '';
        }

        $this->uri = $uri === '' ? '/' : $uri;
        $this->path = str_starts_with($path, '/') || $path === '*' ? $path : '/' . $path;
        $this->queryString = $query;

        if (isset($parts['scheme']) && is_string($parts['scheme'])) {
            $this->scheme = RequestScheme::fromString($parts['scheme']);
        }

        if ($replaceQueryParams) {
            parse_str($query, $params);
            $this->queryParams = $params;
        }
    }
}

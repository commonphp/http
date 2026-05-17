<?php

declare(strict_types=1);

namespace CommonPHP\HTTP;

use CommonPHP\HTTP\Enums\ResponseStatus;
use InvalidArgumentException;

class Response
{
    private int $statusCode;

    private HeaderBag $headers;

    /**
     * @param array<string, mixed>|HeaderBag $headers
     */
    public function __construct(
        string $body = '',
        ResponseStatus|int $status = ResponseStatus::OK,
        array|HeaderBag $headers = [],
        private ?string $reasonPhrase = null,
    ) {
        $this->statusCode = $this->normalizeStatus($status);
        $this->headers = $headers instanceof HeaderBag ? clone $headers : new HeaderBag($headers);
        $this->body = $body;
    }

    private string $body;

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function status(): ?ResponseStatus
    {
        return ResponseStatus::tryFrom($this->statusCode);
    }

    public function reasonPhrase(): string
    {
        return $this->reasonPhrase
            ?? ResponseStatus::tryFrom($this->statusCode)?->reasonPhrase()
            ?? '';
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

    public function body(): string
    {
        return $this->body;
    }

    public function isInformational(): bool
    {
        return $this->statusCode >= 100 && $this->statusCode < 200;
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function isRedirection(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    public function allowsBody(): bool
    {
        return ResponseStatus::codeAllowsBody($this->statusCode);
    }

    public function withStatus(ResponseStatus|int $status, ?string $reasonPhrase = null): static
    {
        $clone = clone $this;
        $clone->statusCode = $this->normalizeStatus($status);
        $clone->reasonPhrase = $reasonPhrase;

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

    public function appendBody(string $body): static
    {
        $clone = clone $this;
        $clone->body .= $body;

        return $clone;
    }

    public function withContentType(string $contentType): static
    {
        return $this->withHeader('Content-Type', $contentType);
    }

    public function withCookie(Cookie $cookie): static
    {
        return $this->withAddedHeader('Set-Cookie', $cookie->toHeader());
    }

    public function withoutCookie(string $name, string $path = '/', ?string $domain = null): static
    {
        return $this->withCookie(Cookie::expired($name, $path, $domain));
    }

    private function normalizeStatus(ResponseStatus|int $status): int
    {
        $status = $status instanceof ResponseStatus ? $status->value : $status;

        if ($status < 100 || $status > 599) {
            throw new InvalidArgumentException('HTTP status code must be between 100 and 599.');
        }

        return $status;
    }
}

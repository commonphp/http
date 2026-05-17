<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Enums;

use CommonPHP\HTTP\Exceptions\UnsupportedMethodException;

enum RequestMethod
{
    case GET;
    case HEAD;
    case POST;
    case PUT;
    case PATCH;
    case DELETE;
    case OPTIONS;
    case TRACE;
    case CONNECT;

    public static function fromString(string $method): self
    {
        $method = strtoupper(trim($method));

        return self::tryFromName($method)
            ?? throw UnsupportedMethodException::forMethod($method);
    }

    public static function tryFromName(string $method): ?self
    {
        return match (strtoupper(trim($method))) {
            'GET' => self::GET,
            'HEAD' => self::HEAD,
            'POST' => self::POST,
            'PUT' => self::PUT,
            'PATCH' => self::PATCH,
            'DELETE' => self::DELETE,
            'OPTIONS' => self::OPTIONS,
            'TRACE' => self::TRACE,
            'CONNECT' => self::CONNECT,
            default => null,
        };
    }

    public function value(): string
    {
        return $this->name;
    }

    public function isSafe(): bool
    {
        return in_array($this, [self::GET, self::HEAD, self::OPTIONS, self::TRACE], true);
    }

    public function isIdempotent(): bool
    {
        return in_array($this, [self::GET, self::HEAD, self::PUT, self::DELETE, self::OPTIONS, self::TRACE], true);
    }

    public function usuallyHasBody(): bool
    {
        return in_array($this, [self::POST, self::PUT, self::PATCH], true);
    }
}

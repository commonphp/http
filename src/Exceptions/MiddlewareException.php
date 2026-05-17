<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Exceptions;

use Throwable;

class MiddlewareException extends HttpException
{
    public static function invalidMiddleware(string $type): self
    {
        return new self('Invalid HTTP middleware: expected MiddlewareInterface or callable, got ' . $type . '.');
    }

    public static function invalidResponse(string $type): self
    {
        return new self('HTTP middleware must return a Response instance, got ' . $type . '.');
    }

    public static function failed(Throwable $previous): self
    {
        return new self('HTTP middleware failed: ' . $previous->getMessage(), 0, $previous);
    }
}

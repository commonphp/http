<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Exceptions;

class UnsupportedMethodException extends HttpException
{
    public static function forMethod(string $method): self
    {
        return new self('Unsupported HTTP method "' . $method . '".');
    }
}

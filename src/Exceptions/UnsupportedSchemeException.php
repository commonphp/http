<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Exceptions;

class UnsupportedSchemeException extends HttpException
{
    public static function forScheme(string $scheme): self
    {
        return new self('Unsupported HTTP scheme "' . $scheme . '".');
    }
}

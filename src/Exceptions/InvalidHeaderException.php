<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Exceptions;

class InvalidHeaderException extends HttpException
{
    public static function forName(string $name): self
    {
        return new self('Invalid HTTP header name "' . $name . '".');
    }

    public static function forValue(string $name): self
    {
        return new self('Invalid HTTP header value for "' . $name . '".');
    }
}

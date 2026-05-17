<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Exceptions;

class InvalidRequestException extends HttpException
{
    public static function because(string $reason): self
    {
        return new self('Invalid HTTP request: ' . $reason);
    }
}

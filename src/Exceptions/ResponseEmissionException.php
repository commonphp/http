<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Exceptions;

class ResponseEmissionException extends HttpException
{
    public static function headersAlreadySent(string $file, int $line): self
    {
        $location = $file === '' ? 'an unknown location' : $file . ':' . $line;

        return new self('Cannot emit HTTP response because headers were already sent at ' . $location . '.');
    }
}

<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Exceptions;

class UploadedFileException extends HttpException
{
    public static function forUpload(string $name, string $message): self
    {
        return new self('Uploaded file "' . $name . '" is not usable: ' . $message);
    }

    public static function cannotMove(string $name, string $target): self
    {
        return new self('Unable to move uploaded file "' . $name . '" to "' . $target . '".');
    }
}

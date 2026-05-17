<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Exceptions;

use CommonPHP\HTTP\Request;

class SurfaceNotFoundException extends HttpException
{
    public static function forRequest(Request $request): self
    {
        return new self('No HTTP surface registered for ' . $request->methodValue() . ' ' . $request->target() . '.');
    }

    public static function forName(string $name): self
    {
        return new self('No HTTP surface registered with name "' . $name . '".');
    }
}

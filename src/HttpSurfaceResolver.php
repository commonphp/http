<?php

declare(strict_types=1);

namespace CommonPHP\HTTP;

use CommonPHP\HTTP\Contracts\HttpSurfaceInterface;
use CommonPHP\HTTP\Exceptions\SurfaceNotFoundException;

class HttpSurfaceResolver
{
    public function __construct(
        private readonly SurfaceRegistry $registry,
    ) {
    }

    public function resolve(Request $request): HttpSurfaceInterface
    {
        return $this->registry->find($request)
            ?? throw SurfaceNotFoundException::forRequest($request);
    }

    public function handle(Request $request): Response
    {
        return $this->resolve($request)->handle($request);
    }
}

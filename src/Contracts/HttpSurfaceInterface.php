<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Contracts;

use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;

interface HttpSurfaceInterface
{
    public function supports(Request $request): bool;

    public function handle(Request $request): Response;
}

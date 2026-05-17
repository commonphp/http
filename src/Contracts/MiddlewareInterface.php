<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Contracts;

use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;

interface MiddlewareInterface
{
    /**
     * @param callable(Request): Response $next
     */
    public function process(Request $request, callable $next): Response;
}

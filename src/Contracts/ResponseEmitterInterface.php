<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Contracts;

use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;

interface ResponseEmitterInterface
{
    public function emit(Response $response, ?Request $request = null): void;
}

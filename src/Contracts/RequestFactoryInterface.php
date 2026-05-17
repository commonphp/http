<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Contracts;

use CommonPHP\HTTP\HeaderBag;
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Enums\RequestMethod;

interface RequestFactoryInterface
{
    /**
     * @param array<string, mixed>|HeaderBag $headers
     * @param array<string, mixed> $query
     * @param array<string, mixed> $cookies
     * @param array<string, mixed> $files
     * @param array<string, mixed> $server
     */
    public function create(
        RequestMethod|string $method = RequestMethod::GET,
        string $uri = '/',
        array|HeaderBag $headers = [],
        string $body = '',
        array $query = [],
        mixed $parsedBody = null,
        array $cookies = [],
        array $files = [],
        array $server = [],
    ): Request;

    /**
     * @param array<string, mixed>|null $server
     * @param array<string, mixed>|null $query
     * @param array<string, mixed>|null $body
     * @param array<string, mixed>|null $cookies
     * @param array<string, mixed>|null $files
     */
    public function fromGlobals(
        ?array $server = null,
        ?array $query = null,
        ?array $body = null,
        ?array $cookies = null,
        ?array $files = null,
        ?string $rawBody = null,
    ): Request;
}

<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Contracts;

use CommonPHP\HTTP\HeaderBag;
use CommonPHP\HTTP\Response;
use CommonPHP\HTTP\Enums\ResponseStatus;

interface ResponseFactoryInterface
{
    /**
     * @param array<string, mixed>|HeaderBag $headers
     */
    public function create(
        ResponseStatus|int $status = ResponseStatus::OK,
        array|HeaderBag $headers = [],
        string $body = '',
    ): Response;

    /**
     * @param array<string, mixed> $headers
     */
    public function text(
        string $body,
        ResponseStatus|int $status = ResponseStatus::OK,
        array $headers = [],
    ): Response;

    /**
     * @param array<string, mixed> $headers
     */
    public function html(
        string $body,
        ResponseStatus|int $status = ResponseStatus::OK,
        array $headers = [],
    ): Response;

    /**
     * @param array<string, mixed> $headers
     */
    public function json(
        mixed $data,
        ResponseStatus|int $status = ResponseStatus::OK,
        array $headers = [],
    ): Response;

    /**
     * @param array<string, mixed> $headers
     */
    public function redirect(
        string $location,
        ResponseStatus|int $status = ResponseStatus::FOUND,
        array $headers = [],
    ): Response;

    /**
     * @param array<string, mixed> $headers
     */
    public function noContent(array $headers = []): Response;
}

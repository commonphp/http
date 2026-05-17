<?php

declare(strict_types=1);

namespace CommonPHP\HTTP;

use CommonPHP\HTTP\Contracts\ResponseFactoryInterface;
use CommonPHP\HTTP\Enums\ResponseStatus;
use JsonException;

class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * @param array<string, mixed>|HeaderBag $headers
     */
    public function create(
        ResponseStatus|int $status = ResponseStatus::OK,
        array|HeaderBag $headers = [],
        string $body = '',
    ): Response {
        return new Response($body, $status, $headers);
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function text(
        string $body,
        ResponseStatus|int $status = ResponseStatus::OK,
        array $headers = [],
    ): Response {
        return $this->create($status, $headers, $body)
            ->withHeader('Content-Type', 'text/plain; charset=utf-8');
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function html(
        string $body,
        ResponseStatus|int $status = ResponseStatus::OK,
        array $headers = [],
    ): Response {
        return $this->create($status, $headers, $body)
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function json(
        mixed $data,
        ResponseStatus|int $status = ResponseStatus::OK,
        array $headers = [],
    ): Response {
        try {
            $body = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $exception) {
            throw new JsonException('Unable to encode HTTP JSON response: ' . $exception->getMessage(), 0, $exception);
        }

        return $this->create($status, $headers, $body)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function redirect(
        string $location,
        ResponseStatus|int $status = ResponseStatus::FOUND,
        array $headers = [],
    ): Response {
        return $this->create($status, $headers)
            ->withHeader('Location', $location);
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function noContent(array $headers = []): Response
    {
        return $this->create(ResponseStatus::NO_CONTENT, $headers);
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function notFound(string $message = 'Not Found', array $headers = []): Response
    {
        return $this->text($message, ResponseStatus::NOT_FOUND, $headers);
    }
}

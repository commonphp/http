<?php

declare(strict_types=1);

namespace CommonPHP\HTTP;

use CommonPHP\HTTP\Contracts\RequestFactoryInterface;
use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Enums\RequestScheme;
use CommonPHP\HTTP\Exceptions\InvalidRequestException;
use JsonException;

class RequestFactory implements RequestFactoryInterface
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
    ): Request {
        return new Request(
            $method,
            $uri,
            $headers,
            $body,
            $query,
            $parsedBody,
            $cookies,
            UploadedFile::normalizeArray($files),
            $server,
            $server === [] ? null : RequestScheme::fromServer($server),
        );
    }

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
    ): Request {
        $server ??= $_SERVER;
        $query ??= $_GET;
        $body ??= $_POST;
        $cookies ??= $_COOKIE;
        $files ??= $_FILES;
        $rawBody ??= $this->readInput();

        $headers = HeaderBag::fromServer($server);
        $method = isset($server['REQUEST_METHOD']) ? (string) $server['REQUEST_METHOD'] : 'GET';
        $uri = isset($server['REQUEST_URI']) ? (string) $server['REQUEST_URI'] : '/';
        $parsedBody = $this->parseBody($headers, $body, $rawBody);

        return $this->create(
            $method,
            $uri,
            $headers,
            $rawBody,
            $query,
            $parsedBody,
            $cookies,
            $files,
            $server,
        );
    }

    private function readInput(): string
    {
        $body = @file_get_contents('php://input');

        return $body === false ? '' : $body;
    }

    /**
     * @param array<string, mixed> $postBody
     */
    private function parseBody(HeaderBag $headers, array $postBody, string $rawBody): mixed
    {
        if ($postBody !== []) {
            return $postBody;
        }

        if ($rawBody === '') {
            return [];
        }

        $contentType = strtolower($headers->first('Content-Type', ''));

        if (str_contains($contentType, '/json') || str_contains($contentType, '+json')) {
            try {
                return json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw InvalidRequestException::because('Invalid JSON body: ' . $exception->getMessage());
            }
        }

        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($rawBody, $parsed);

            return $parsed;
        }

        return null;
    }
}

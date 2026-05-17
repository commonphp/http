<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Tests\Unit;

use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\HTTP\HeaderBag;
use CommonPHP\HTTP\ResponseFactory;
use JsonException;
use PHPUnit\Framework\TestCase;

final class ResponseFactoryTest extends TestCase
{
    public function testItCreatesGenericResponses(): void
    {
        $headers = new HeaderBag(['X-Test' => 'yes']);
        $response = (new ResponseFactory())->create(ResponseStatus::ACCEPTED, $headers, 'queued');

        self::assertSame(202, $response->statusCode());
        self::assertSame('yes', $response->header('X-Test'));
        self::assertSame('queued', $response->body());
    }

    public function testItCreatesTextAndHtmlResponses(): void
    {
        $factory = new ResponseFactory();
        $text = $factory->text('hello', 201, ['X-Test' => 'yes']);
        $html = $factory->html('<h1>Hello</h1>');

        self::assertSame(201, $text->statusCode());
        self::assertSame('text/plain; charset=utf-8', $text->header('Content-Type'));
        self::assertSame('yes', $text->header('X-Test'));
        self::assertSame('text/html; charset=utf-8', $html->header('Content-Type'));
    }

    public function testItCreatesJsonResponses(): void
    {
        $response = (new ResponseFactory())->json(['ok' => true, 'path' => '/api']);

        self::assertSame(200, $response->statusCode());
        self::assertSame('application/json; charset=utf-8', $response->header('Content-Type'));
        self::assertSame('{"ok":true,"path":"/api"}', $response->body());
    }

    public function testItRejectsUnencodableJsonResponses(): void
    {
        $resource = fopen('php://memory', 'r');
        self::assertIsResource($resource);

        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Unable to encode HTTP JSON response');

        try {
            (new ResponseFactory())->json(['resource' => $resource]);
        } finally {
            fclose($resource);
        }
    }

    public function testItCreatesRedirectNoContentAndNotFoundResponses(): void
    {
        $factory = new ResponseFactory();
        $redirect = $factory->redirect('/login');
        $noContent = $factory->noContent(['X-Test' => 'yes']);
        $notFound = $factory->notFound('Missing');

        self::assertSame(302, $redirect->statusCode());
        self::assertSame('/login', $redirect->header('Location'));
        self::assertSame(204, $noContent->statusCode());
        self::assertSame('yes', $noContent->header('X-Test'));
        self::assertSame(404, $notFound->statusCode());
        self::assertSame('Missing', $notFound->body());
    }
}

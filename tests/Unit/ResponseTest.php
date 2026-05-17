<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Tests\Unit;

use CommonPHP\HTTP\Cookie;
use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\HTTP\HeaderBag;
use CommonPHP\HTTP\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function testItExposesStatusHeadersAndBody(): void
    {
        $response = new Response('Created', ResponseStatus::CREATED, ['Content-Type' => 'text/plain']);

        self::assertSame(201, $response->statusCode());
        self::assertSame(ResponseStatus::CREATED, $response->status());
        self::assertSame('Created', $response->reasonPhrase());
        self::assertInstanceOf(HeaderBag::class, $response->headers());
        self::assertTrue($response->hasHeader('content-type'));
        self::assertSame('text/plain', $response->header('Content-Type'));
        self::assertSame('Created', $response->body());
        self::assertTrue($response->isSuccessful());
        self::assertTrue($response->allowsBody());
    }

    public function testItClassifiesResponseCodes(): void
    {
        self::assertTrue((new Response(status: 101))->isInformational());
        self::assertTrue((new Response(status: 204))->isSuccessful());
        self::assertTrue((new Response(status: 302))->isRedirection());
        self::assertTrue((new Response(status: 404))->isClientError());
        self::assertTrue((new Response(status: 500))->isServerError());
        self::assertFalse((new Response(status: 204))->allowsBody());
        self::assertFalse((new Response(status: 304))->allowsBody());
    }

    public function testItCanCloneWithChangedValues(): void
    {
        $response = new Response('old', 200, ['X-Original' => 'yes']);
        $changed = $response
            ->withStatus(202, 'Queued')
            ->withHeader('X-Test', 'one')
            ->withAddedHeader('X-Test', 'two')
            ->withoutHeader('X-Original')
            ->withBody('new')
            ->appendBody(' body')
            ->withContentType('application/json')
            ->withCookie(new Cookie('sid', 'abc'))
            ->withoutCookie('old');

        self::assertSame(200, $response->statusCode());
        self::assertSame('old', $response->body());
        self::assertTrue($response->hasHeader('X-Original'));

        self::assertSame(202, $changed->statusCode());
        self::assertSame('Queued', $changed->reasonPhrase());
        self::assertSame('new body', $changed->body());
        self::assertFalse($changed->hasHeader('X-Original'));
        self::assertSame(['one', 'two'], $changed->headers()->values('X-Test'));
        self::assertSame('application/json', $changed->header('Content-Type'));
        self::assertCount(2, $changed->headers()->values('Set-Cookie'));
    }

    public function testItSupportsUnknownValidStatusCodes(): void
    {
        $response = new Response('custom', 299);

        self::assertSame(299, $response->statusCode());
        self::assertNull($response->status());
        self::assertSame('', $response->reasonPhrase());
        self::assertTrue($response->isSuccessful());
    }

    public function testItRejectsInvalidStatusCodes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('HTTP status code must be between 100 and 599.');

        new Response(status: 99);
    }
}

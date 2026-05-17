<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Tests\Unit;

use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Enums\RequestScheme;
use CommonPHP\HTTP\Exceptions\InvalidRequestException;
use CommonPHP\HTTP\HeaderBag;
use CommonPHP\HTTP\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testItExposesRequestData(): void
    {
        $request = new Request(
            RequestMethod::POST,
            '/submit?draft=1',
            ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'Host' => 'example.test'],
            '{"name":"Ada"}',
            ['draft' => '1'],
            ['name' => 'Ada'],
            ['sid' => 'abc'],
            ['avatar' => 'file'],
            ['REMOTE_ADDR' => '127.0.0.1'],
            RequestScheme::HTTPS,
            ['route' => 'submit'],
        );

        self::assertSame(RequestMethod::POST, $request->method());
        self::assertSame('POST', $request->methodValue());
        self::assertTrue($request->isMethod('post'));
        self::assertSame(RequestScheme::HTTPS, $request->scheme());
        self::assertTrue($request->isSecure());
        self::assertSame('/submit?draft=1', $request->uri());
        self::assertSame('/submit', $request->path());
        self::assertSame('draft=1', $request->queryString());
        self::assertSame('/submit?draft=1', $request->target());
        self::assertSame('https://example.test/submit?draft=1', $request->fullUri());
        self::assertSame('example.test', $request->host());
        self::assertSame('127.0.0.1', $request->ip());
        self::assertTrue($request->hasHeader('content-type'));
        self::assertSame('application/json', $request->header('Content-Type'));
        self::assertSame('application/json', $request->firstHeader('Accept'));
        self::assertInstanceOf(HeaderBag::class, $request->headers());
        self::assertSame('{"name":"Ada"}', $request->body());
        self::assertSame(['name' => 'Ada'], $request->parsedBody());
        self::assertSame(['draft' => '1'], $request->queryParams());
        self::assertSame('1', $request->query('draft'));
        self::assertSame('fallback', $request->query('missing', 'fallback'));
        self::assertSame(['sid' => 'abc'], $request->cookies());
        self::assertSame('abc', $request->cookie('sid'));
        self::assertSame(['avatar' => 'file'], $request->files());
        self::assertSame('file', $request->file('avatar'));
        self::assertSame(['REMOTE_ADDR' => '127.0.0.1'], $request->serverParams());
        self::assertSame('127.0.0.1', $request->server('REMOTE_ADDR'));
        self::assertSame(['route' => 'submit'], $request->attributes());
        self::assertSame('submit', $request->attribute('route'));
        self::assertTrue($request->isJson());
        self::assertTrue($request->wantsJson());
        self::assertTrue($request->accepts('application/json'));
        self::assertSame(['name' => 'Ada'], $request->json());
    }

    public function testItInfersAbsoluteUriSchemeAndHost(): void
    {
        $request = new Request('GET', 'https://example.test/path?x=1');

        self::assertSame(RequestScheme::HTTPS, $request->scheme());
        self::assertSame('example.test', $request->host());
        self::assertSame('/path', $request->path());
        self::assertSame('x=1', $request->queryString());
        self::assertSame(['x' => '1'], $request->queryParams());
        self::assertSame('https://example.test/path?x=1', $request->fullUri());
    }

    public function testItCanCloneWithChangedValues(): void
    {
        $request = new Request('GET', '/old', ['X-Original' => 'yes'], 'body', attributes: ['keep' => true]);

        $changed = $request
            ->withMethod('PATCH')
            ->withUri('/new?x=1')
            ->withHeader('X-Test', 'one')
            ->withAddedHeader('X-Test', 'two')
            ->withoutHeader('X-Original')
            ->withBody('changed')
            ->withParsedBody(['changed' => true])
            ->withAttribute('id', 42)
            ->withoutAttribute('keep');

        self::assertSame('GET', $request->methodValue());
        self::assertSame('/old', $request->path());
        self::assertTrue($request->hasHeader('X-Original'));
        self::assertSame('body', $request->body());
        self::assertSame(['keep' => true], $request->attributes());

        self::assertSame('PATCH', $changed->methodValue());
        self::assertSame('/new', $changed->path());
        self::assertSame(['x' => '1'], $changed->queryParams());
        self::assertFalse($changed->hasHeader('X-Original'));
        self::assertSame(['one', 'two'], $changed->headers()->values('X-Test'));
        self::assertSame('changed', $changed->body());
        self::assertSame(['changed' => true], $changed->parsedBody());
        self::assertSame(42, $changed->attribute('id'));
        self::assertNull($changed->attribute('keep'));
    }

    public function testItUsesServerDataForSchemeHostAndIp(): void
    {
        $request = new Request('GET', '/secure', serverParams: [
            'HTTPS' => 'on',
            'SERVER_NAME' => 'server.test',
            'REMOTE_ADDR' => '10.0.0.1',
        ]);

        self::assertSame(RequestScheme::HTTPS, $request->scheme());
        self::assertSame('server.test', $request->host());
        self::assertSame('10.0.0.1', $request->ip());
    }

    public function testItHandlesAcceptWildcards(): void
    {
        self::assertTrue((new Request(headers: ['Accept' => '*/*']))->accepts('text/html'));
        self::assertTrue((new Request(headers: ['Accept' => 'text/*']))->accepts('text/html'));
        self::assertFalse((new Request(headers: ['Accept' => 'application/json']))->accepts('text/html'));
    }

    public function testItRejectsMalformedUris(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Malformed URI');

        new Request('GET', 'http://');
    }

    public function testItRejectsInvalidJsonBodies(): void
    {
        $request = new Request('POST', '/json', ['Content-Type' => 'application/json'], '{bad');

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Invalid JSON body');

        $request->json();
    }
}

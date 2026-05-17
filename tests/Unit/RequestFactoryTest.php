<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Tests\Unit;

use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Exceptions\InvalidRequestException;
use CommonPHP\HTTP\RequestFactory;
use CommonPHP\HTTP\UploadedFile;
use PHPUnit\Framework\TestCase;

final class RequestFactoryTest extends TestCase
{
    public function testItCreatesRequestsDirectly(): void
    {
        $factory = new RequestFactory();
        $request = $factory->create(
            RequestMethod::PUT,
            '/items/1',
            ['Content-Type' => 'text/plain'],
            'payload',
            ['page' => '2'],
            ['payload' => true],
            ['sid' => 'abc'],
            [],
            ['HTTPS' => 'on'],
        );

        self::assertSame('PUT', $request->methodValue());
        self::assertSame('/items/1', $request->path());
        self::assertSame('text/plain', $request->firstHeader('Content-Type'));
        self::assertSame('payload', $request->body());
        self::assertSame(['page' => '2'], $request->queryParams());
        self::assertSame(['payload' => true], $request->parsedBody());
        self::assertSame(['sid' => 'abc'], $request->cookies());
        self::assertTrue($request->isSecure());
    }

    public function testItCreatesRequestsFromGlobalsWithJsonBody(): void
    {
        $request = (new RequestFactory())->fromGlobals(
            [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/api/users?active=1',
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_HOST' => 'api.test',
            ],
            ['active' => '1'],
            [],
            ['sid' => 'cookie'],
            [],
            '{"name":"Ada"}',
        );

        self::assertSame('POST', $request->methodValue());
        self::assertSame('/api/users', $request->path());
        self::assertSame(['active' => '1'], $request->queryParams());
        self::assertSame(['name' => 'Ada'], $request->parsedBody());
        self::assertSame('api.test', $request->host());
        self::assertSame('application/json', $request->firstHeader('Accept'));
    }

    public function testItCreatesRequestsFromGlobalsWithFormBody(): void
    {
        $request = (new RequestFactory())->fromGlobals(
            [
                'REQUEST_METHOD' => 'PATCH',
                'REQUEST_URI' => '/profile',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            ],
            [],
            [],
            [],
            [],
            'name=Ada&role=admin',
        );

        self::assertSame(['name' => 'Ada', 'role' => 'admin'], $request->parsedBody());
    }

    public function testItPrefersPostedBodyArraysWhenProvided(): void
    {
        $request = (new RequestFactory())->fromGlobals(
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/profile'],
            [],
            ['name' => 'Posted'],
            [],
            [],
            'name=Raw',
        );

        self::assertSame(['name' => 'Posted'], $request->parsedBody());
    }

    public function testItReturnsNullParsedBodyForUnknownRawContentTypes(): void
    {
        $request = (new RequestFactory())->fromGlobals(
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/profile', 'CONTENT_TYPE' => 'text/plain'],
            rawBody: 'plain text',
        );

        self::assertNull($request->parsedBody());
    }

    public function testItNormalizesUploadedFiles(): void
    {
        $request = (new RequestFactory())->create(
            'POST',
            '/upload',
            files: [
                'document' => [
                    'name' => 'one.txt',
                    'type' => 'text/plain',
                    'tmp_name' => 'tmp-path',
                    'error' => UPLOAD_ERR_OK,
                    'size' => 3,
                ],
            ],
        );

        self::assertInstanceOf(UploadedFile::class, $request->file('document'));
    }

    public function testItRejectsInvalidJsonFromGlobals(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Invalid JSON body');

        (new RequestFactory())->fromGlobals(
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/json', 'CONTENT_TYPE' => 'application/json'],
            rawBody: '{bad',
        );
    }
}

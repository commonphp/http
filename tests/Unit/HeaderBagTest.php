<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Tests\Unit;

use CommonPHP\HTTP\Exceptions\InvalidHeaderException;
use CommonPHP\HTTP\HeaderBag;
use PHPUnit\Framework\TestCase;

final class HeaderBagTest extends TestCase
{
    public function testItStoresHeadersCaseInsensitivelyWithCanonicalNames(): void
    {
        $headers = new HeaderBag([
            'content-type' => 'application/json',
            'X-Test' => ['first', 'second'],
        ]);

        self::assertCount(2, $headers);
        self::assertTrue($headers->has('Content-Type'));
        self::assertSame('application/json', $headers->get('CONTENT-TYPE'));
        self::assertSame('first', $headers->first('x-test'));
        self::assertSame(['first', 'second'], $headers->values('X-Test'));
        self::assertSame(['Content-Type', 'X-Test'], $headers->names());
        self::assertSame(
            [
                'Content-Type' => ['application/json'],
                'X-Test' => ['first', 'second'],
            ],
            $headers->all(),
        );
    }

    public function testItAddsRemovesMergesAndClonesHeaders(): void
    {
        $headers = new HeaderBag(['Accept' => 'text/html']);

        self::assertSame($headers, $headers->add('Accept', 'application/json'));
        self::assertSame('text/html, application/json', $headers->get('accept'));

        $with = $headers->with('X-Trace', 'abc');
        $withAdded = $headers->withAdded('Accept', 'application/xml');
        $without = $headers->without('Accept');

        self::assertFalse($headers->has('X-Trace'));
        self::assertSame('abc', $with->first('x-trace'));
        self::assertSame(['text/html', 'application/json', 'application/xml'], $withAdded->values('accept'));
        self::assertFalse($without->has('Accept'));

        $headers->merge(new HeaderBag(['Cache-Control' => 'no-store']));
        $headers->merge(['X-Mode' => ['debug']]);

        self::assertSame('no-store', $headers->first('cache-control'));
        self::assertSame('debug', $headers->first('x-mode'));

        self::assertSame($headers, $headers->remove('X-Mode'));
        self::assertFalse($headers->has('x-mode'));
        self::assertFalse($headers->isEmpty());
    }

    public function testItBuildsHeadersFromServerParameters(): void
    {
        $headers = HeaderBag::fromServer([
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'text/plain',
            'CONTENT_LENGTH' => 12,
            'SERVER_NAME' => 'example.test',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_IGNORED_ARRAY' => ['nope'],
        ]);

        self::assertSame('application/json', $headers->first('Accept'));
        self::assertSame('text/plain', $headers->first('Content-Type'));
        self::assertSame('12', $headers->first('Content-Length'));
        self::assertSame('https', $headers->first('X-Forwarded-Proto'));
        self::assertFalse($headers->has('Ignored-Array'));
        self::assertFalse($headers->has('Server-Name'));
    }

    public function testItRejectsInvalidHeaderNames(): void
    {
        $this->expectException(InvalidHeaderException::class);
        $this->expectExceptionMessage('Invalid HTTP header name');

        new HeaderBag(["Bad\nName" => 'value']);
    }

    public function testItRejectsInvalidHeaderValues(): void
    {
        $this->expectException(InvalidHeaderException::class);
        $this->expectExceptionMessage('Invalid HTTP header value');

        new HeaderBag(['X-Test' => "bad\r\nvalue"]);
    }

    public function testItRejectsEmptyHeaderValueLists(): void
    {
        $this->expectException(InvalidHeaderException::class);

        (new HeaderBag())->set('X-Test', []);
    }

    public function testItReturnsDefaultsForMissingHeaders(): void
    {
        $headers = new HeaderBag();

        self::assertNull($headers->get('Missing'));
        self::assertSame('fallback', $headers->get('Missing', 'fallback'));
        self::assertSame('fallback', $headers->first('Missing', 'fallback'));
        self::assertSame([], $headers->values('Missing'));
        self::assertTrue($headers->isEmpty());
    }
}

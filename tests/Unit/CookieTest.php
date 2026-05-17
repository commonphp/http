<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Tests\Unit;

use CommonPHP\HTTP\Cookie;
use CommonPHP\HTTP\Exceptions\InvalidHeaderException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CookieTest extends TestCase
{
    public function testItBuildsSetCookieHeaders(): void
    {
        $expires = new DateTimeImmutable('+1 hour');
        $cookie = new Cookie('session', 'abc 123', $expires, '/account', 'example.test', true, true, 'Strict');
        $header = $cookie->toHeader();

        self::assertSame('session', $cookie->name());
        self::assertSame('abc 123', $cookie->value());
        self::assertSame($expires, $cookie->expires());
        self::assertSame('/account', $cookie->path());
        self::assertSame('example.test', $cookie->domain());
        self::assertTrue($cookie->isSecure());
        self::assertTrue($cookie->isHttpOnly());
        self::assertSame('Strict', $cookie->sameSite());
        self::assertStringStartsWith('session=abc%20123; Expires=', $header);
        self::assertStringContainsString('; Max-Age=', $header);
        self::assertStringContainsString('; Path=/account', $header);
        self::assertStringContainsString('; Domain=example.test', $header);
        self::assertStringContainsString('; Secure', $header);
        self::assertStringContainsString('; HttpOnly', $header);
        self::assertStringContainsString('; SameSite=Strict', $header);
        self::assertSame($header, (string) $cookie);
    }

    public function testItCanUseRawValuesAndNullableSameSite(): void
    {
        $cookie = new Cookie('raw', 'a=b', sameSite: null, raw: true);

        self::assertSame('raw=a=b; Path=/; HttpOnly', $cookie->toHeader());
    }

    public function testItCanCreateUpdatedAndExpiredCookies(): void
    {
        $cookie = new Cookie('theme', 'light');
        $updated = $cookie->withValue('dark');
        $expired = $cookie->expire();
        $staticExpired = Cookie::expired('theme');

        self::assertSame('light', $cookie->value());
        self::assertSame('dark', $updated->value());
        self::assertTrue($expired->isExpired(new DateTimeImmutable()));
        self::assertTrue($staticExpired->isExpired(new DateTimeImmutable()));
        self::assertStringContainsString('Max-Age=0', $expired->toHeader());
    }

    public function testItRejectsInvalidCookieNames(): void
    {
        $this->expectException(InvalidHeaderException::class);

        new Cookie('bad name', 'value');
    }

    public function testItRejectsInvalidCookieValues(): void
    {
        $this->expectException(InvalidHeaderException::class);

        new Cookie('good', "bad\nvalue");
    }

    public function testItRejectsInvalidSameSiteValues(): void
    {
        $this->expectException(InvalidHeaderException::class);

        new Cookie('good', 'value', sameSite: 'Sometimes');
    }
}

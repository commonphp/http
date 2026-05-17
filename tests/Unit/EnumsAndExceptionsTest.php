<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Tests\Unit;

use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Enums\RequestScheme;
use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\HTTP\Exceptions\HttpException;
use CommonPHP\HTTP\Exceptions\InvalidHeaderException;
use CommonPHP\HTTP\Exceptions\InvalidRequestException;
use CommonPHP\HTTP\Exceptions\MiddlewareException;
use CommonPHP\HTTP\Exceptions\ResponseEmissionException;
use CommonPHP\HTTP\Exceptions\SurfaceNotFoundException;
use CommonPHP\HTTP\Exceptions\UnsupportedMethodException;
use CommonPHP\HTTP\Exceptions\UnsupportedSchemeException;
use CommonPHP\HTTP\Exceptions\UploadedFileException;
use CommonPHP\HTTP\Request;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EnumsAndExceptionsTest extends TestCase
{
    public function testRequestMethodsParseAndDescribeBehavior(): void
    {
        self::assertSame(RequestMethod::GET, RequestMethod::fromString('get'));
        self::assertSame(RequestMethod::POST, RequestMethod::tryFromName('POST'));
        self::assertNull(RequestMethod::tryFromName('BREW'));
        self::assertSame('PATCH', RequestMethod::PATCH->value());
        self::assertTrue(RequestMethod::GET->isSafe());
        self::assertFalse(RequestMethod::POST->isSafe());
        self::assertTrue(RequestMethod::PUT->isIdempotent());
        self::assertFalse(RequestMethod::PATCH->isIdempotent());
        self::assertTrue(RequestMethod::PATCH->usuallyHasBody());
        self::assertFalse(RequestMethod::GET->usuallyHasBody());
    }

    public function testRequestMethodsRejectUnsupportedValues(): void
    {
        $this->expectException(UnsupportedMethodException::class);
        $this->expectExceptionMessage('Unsupported HTTP method "BREW".');

        RequestMethod::fromString('BREW');
    }

    public function testRequestSchemesParseFromStringsAndServerData(): void
    {
        self::assertSame(RequestScheme::HTTP, RequestScheme::fromString('http'));
        self::assertSame(RequestScheme::HTTPS, RequestScheme::fromString('HTTPS'));
        self::assertSame('https', RequestScheme::HTTPS->value());
        self::assertTrue(RequestScheme::HTTPS->isSecure());
        self::assertFalse(RequestScheme::HTTP->isSecure());
        self::assertSame(RequestScheme::HTTPS, RequestScheme::fromServer(['REQUEST_SCHEME' => 'https']));
        self::assertSame(RequestScheme::HTTPS, RequestScheme::fromServer(['HTTP_X_FORWARDED_PROTO' => 'https, http']));
        self::assertSame(RequestScheme::HTTPS, RequestScheme::fromServer(['HTTPS' => 'on']));
        self::assertSame(RequestScheme::HTTPS, RequestScheme::fromServer(['SERVER_PORT' => '443']));
        self::assertSame(RequestScheme::HTTP, RequestScheme::fromServer([]));
    }

    public function testRequestSchemesRejectUnsupportedValues(): void
    {
        $this->expectException(UnsupportedSchemeException::class);
        $this->expectExceptionMessage('Unsupported HTTP scheme "ftp".');

        RequestScheme::fromString('ftp');
    }

    public function testResponseStatusesExposeReasonPhrasesAndClasses(): void
    {
        self::assertSame('OK', ResponseStatus::OK->reasonPhrase());
        self::assertSame("I'm a teapot", ResponseStatus::IM_A_TEAPOT->reasonPhrase());
        self::assertTrue(ResponseStatus::CONTINUE_RESPONSE->isInformational());
        self::assertTrue(ResponseStatus::OK->isSuccessful());
        self::assertTrue(ResponseStatus::FOUND->isRedirection());
        self::assertTrue(ResponseStatus::NOT_FOUND->isClientError());
        self::assertTrue(ResponseStatus::INTERNAL_SERVER_ERROR->isServerError());
        self::assertTrue(ResponseStatus::OK->allowsBody());
        self::assertFalse(ResponseStatus::NO_CONTENT->allowsBody());
        self::assertFalse(ResponseStatus::codeAllowsBody(304));
        self::assertTrue(ResponseStatus::codeAllowsBody(299));
    }

    public function testExceptionHelpersCreateUsefulMessages(): void
    {
        $request = new Request('GET', '/missing');
        $previous = new RuntimeException('boom');

        self::assertInstanceOf(HttpException::class, InvalidHeaderException::forName('bad'));
        self::assertStringContainsString('Invalid HTTP header value', InvalidHeaderException::forValue('X-Test')->getMessage());
        self::assertStringContainsString('Invalid HTTP request', InvalidRequestException::because('broken')->getMessage());
        self::assertStringContainsString('Invalid HTTP middleware', MiddlewareException::invalidMiddleware('array')->getMessage());
        self::assertStringContainsString('must return a Response', MiddlewareException::invalidResponse('string')->getMessage());
        self::assertSame($previous, MiddlewareException::failed($previous)->getPrevious());
        self::assertStringContainsString('headers were already sent', ResponseEmissionException::headersAlreadySent('file.php', 10)->getMessage());
        self::assertStringContainsString('GET /missing', SurfaceNotFoundException::forRequest($request)->getMessage());
        self::assertStringContainsString('"api"', SurfaceNotFoundException::forName('api')->getMessage());
        self::assertStringContainsString('"BREW"', UnsupportedMethodException::forMethod('BREW')->getMessage());
        self::assertStringContainsString('"ftp"', UnsupportedSchemeException::forScheme('ftp')->getMessage());
        self::assertStringContainsString('not usable', UploadedFileException::forUpload('avatar', 'bad')->getMessage());
        self::assertStringContainsString('Unable to move uploaded file', UploadedFileException::cannotMove('avatar', '/tmp/a')->getMessage());
    }
}

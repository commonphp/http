<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Tests\Unit;

use CommonPHP\HTTP\Contracts\RequestFactoryInterface;
use CommonPHP\HTTP\Contracts\ResponseEmitterInterface;
use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Exceptions\HttpException;
use CommonPHP\HTTP\Exceptions\InvalidRequestException;
use CommonPHP\HTTP\Exceptions\MiddlewareException;
use CommonPHP\HTTP\Exceptions\UnsupportedMethodException;
use CommonPHP\HTTP\Exceptions\UploadedFileException;
use CommonPHP\HTTP\HeaderBag;
use CommonPHP\HTTP\HttpExecutive;
use CommonPHP\HTTP\HttpSurfaceResolver;
use CommonPHP\HTTP\MiddlewarePipeline;
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\HTTP\ResponseFactory;
use CommonPHP\HTTP\SurfaceRegistry;
use CommonPHP\Runtime\Support\ExitStatus;
use PHPUnit\Framework\TestCase;

final class HttpExecutiveTest extends TestCase
{
    public function testItDispatchesRequestsThroughMiddlewareAndSurfaces(): void
    {
        $request = new Request('GET', '/api/ping');
        $surface = new class implements \CommonPHP\HTTP\Contracts\HttpSurfaceInterface {
            public function supports(Request $request): bool
            {
                return $request->path() === '/api/ping';
            }

            public function handle(Request $request): Response
            {
                return new Response('pong');
            }
        };
        $registry = (new SurfaceRegistry())->register('api', $surface, '/api');
        $emitter = $this->emitter();
        $pipeline = (new MiddlewarePipeline())->pipe(
            static fn (Request $request, callable $next): Response => $next($request)->withHeader('X-Pipeline', 'yes'),
        );

        $status = (new HttpExecutive(
            $this->factoryReturning($request),
            $emitter,
            new ResponseFactory(),
            $pipeline,
            new HttpSurfaceResolver($registry),
        ))->execute();

        self::assertSame(ExitStatus::SUCCESS, $status);
        self::assertSame('pong', $emitter->response->body());
        self::assertSame('yes', $emitter->response->header('X-Pipeline'));
        self::assertSame($request, $emitter->request);
    }

    public function testItConvertsMissingSurfacesToNotFoundResponses(): void
    {
        $emitter = $this->emitter();

        $status = (new HttpExecutive(
            $this->factoryReturning(new Request('GET', '/missing')),
            $emitter,
            new ResponseFactory(),
            new MiddlewarePipeline(),
            new HttpSurfaceResolver(new SurfaceRegistry()),
        ))->execute();

        self::assertSame(ExitStatus::SUCCESS, $status);
        self::assertSame(404, $emitter->response->statusCode());
        self::assertStringContainsString('No HTTP surface registered', $emitter->response->body());
    }

    public function testItConvertsMiddlewareFailuresToServerErrors(): void
    {
        $emitter = $this->emitter();
        $pipeline = new MiddlewarePipeline([
            static fn (): string => 'bad',
        ]);

        $status = (new HttpExecutive(
            $this->factoryReturning(new Request('GET', '/')),
            $emitter,
            new ResponseFactory(),
            $pipeline,
            new HttpSurfaceResolver(new SurfaceRegistry()),
        ))->execute();

        self::assertSame(ExitStatus::EXCEPTION, $status);
        self::assertSame(500, $emitter->response->statusCode());
        self::assertStringContainsString('must return a Response', $emitter->response->body());
    }

    public function testItConvertsRequestFactoryHttpExceptionsToResponses(): void
    {
        $cases = [
            [UnsupportedMethodException::forMethod('BREW'), 405, ExitStatus::SUCCESS],
            [InvalidRequestException::because('broken'), 400, ExitStatus::SUCCESS],
            [UploadedFileException::forUpload('avatar', 'bad'), 400, ExitStatus::SUCCESS],
            [MiddlewareException::invalidResponse('string'), 500, ExitStatus::EXCEPTION],
            [new HttpException('generic'), 500, ExitStatus::EXCEPTION],
        ];

        foreach ($cases as [$exception, $expectedStatus, $expectedExit]) {
            $emitter = $this->emitter();
            $status = (new HttpExecutive(
                $this->factoryThrowing($exception),
                $emitter,
                new ResponseFactory(),
                new MiddlewarePipeline(),
                new HttpSurfaceResolver(new SurfaceRegistry()),
            ))->execute();

            self::assertSame($expectedExit, $status);
            self::assertSame($expectedStatus, $emitter->response->statusCode());
            self::assertSame('text/plain; charset=utf-8', $emitter->response->header('Content-Type'));
        }
    }

    public function testItProvidesDefaultCollaborators(): void
    {
        $emitter = $this->emitter();
        $executive = new HttpExecutive($this->factoryReturning(new Request('GET', '/missing')), $emitter);

        self::assertSame(ExitStatus::SUCCESS, $executive->execute());
        self::assertSame(404, $emitter->response->statusCode());
    }

    private function factoryReturning(Request $request): RequestFactoryInterface
    {
        return new class($request) implements RequestFactoryInterface {
            public function __construct(private readonly Request $request)
            {
            }

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
                return $this->request;
            }

            public function fromGlobals(
                ?array $server = null,
                ?array $query = null,
                ?array $body = null,
                ?array $cookies = null,
                ?array $files = null,
                ?string $rawBody = null,
            ): Request {
                return $this->request;
            }
        };
    }

    private function factoryThrowing(HttpException $exception): RequestFactoryInterface
    {
        return new class($exception) implements RequestFactoryInterface {
            public function __construct(private readonly HttpException $exception)
            {
            }

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
                throw $this->exception;
            }

            public function fromGlobals(
                ?array $server = null,
                ?array $query = null,
                ?array $body = null,
                ?array $cookies = null,
                ?array $files = null,
                ?string $rawBody = null,
            ): Request {
                throw $this->exception;
            }
        };
    }

    private function emitter(): ResponseEmitterInterface
    {
        return new class implements ResponseEmitterInterface {
            public Response $response;

            public ?Request $request = null;

            public function emit(Response $response, ?Request $request = null): void
            {
                $this->response = $response;
                $this->request = $request;
            }
        };
    }
}

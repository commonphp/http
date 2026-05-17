<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Tests\Unit;

use CommonPHP\HTTP\Contracts\MiddlewareInterface;
use CommonPHP\HTTP\Exceptions\MiddlewareException;
use CommonPHP\HTTP\MiddlewarePipeline;
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use PHPUnit\Framework\TestCase;

final class MiddlewarePipelineTest extends TestCase
{
    public function testItRunsMiddlewareAroundTheFallbackHandler(): void
    {
        $log = new class {
            /**
             * @var list<string>
             */
            public array $events = [];
        };
        $pipeline = new MiddlewarePipeline([
            static function (Request $request, callable $next) use ($log): Response {
                $log->events[] = 'first-before';
                $response = $next($request->withAttribute('first', true));
                $log->events[] = 'first-after';

                return $response->withHeader('X-First', 'yes');
            },
            new class($log) implements MiddlewareInterface {
                public function __construct(private readonly object $log)
                {
                }

                public function process(Request $request, callable $next): Response
                {
                    $this->log->events[] = 'second-before';
                    $response = $next($request->withAttribute('second', true));
                    $this->log->events[] = 'second-after';

                    return $response->withHeader('X-Second', 'yes');
                }
            },
        ]);

        $response = $pipeline->handle(
            new Request(),
            static function (Request $request) use ($log): Response {
                $log->events[] = $request->attribute('first') && $request->attribute('second') ? 'handler' : 'missing';

                return new Response('ok');
            },
        );

        self::assertSame(['first-before', 'second-before', 'handler', 'second-after', 'first-after'], $log->events);
        self::assertSame('yes', $response->header('X-First'));
        self::assertSame('yes', $response->header('X-Second'));
        self::assertSame('ok', $response->body());
        self::assertFalse($pipeline->isEmpty());
        self::assertCount(2, $pipeline->all());
    }

    public function testItCanPipeMiddlewareAfterConstruction(): void
    {
        $pipeline = new MiddlewarePipeline();

        self::assertTrue($pipeline->isEmpty());
        self::assertSame($pipeline, $pipeline->pipe(
            static fn (Request $request, callable $next): Response => $next($request)->withHeader('X-Piped', 'yes'),
        ));

        $response = $pipeline->handle(new Request(), static fn (): Response => new Response('ok'));

        self::assertSame('yes', $response->header('X-Piped'));
    }

    public function testItUsesDefaultFallbackWhenNoHandlerIsProvided(): void
    {
        $response = (new MiddlewarePipeline())->handle(new Request());

        self::assertSame(404, $response->statusCode());
        self::assertSame('Not Found', $response->body());
        self::assertSame('text/plain; charset=utf-8', $response->header('Content-Type'));
    }

    public function testItRejectsMiddlewareThatReturnsNonResponses(): void
    {
        $pipeline = new MiddlewarePipeline([
            static fn (): string => 'not a response',
        ]);

        $this->expectException(MiddlewareException::class);
        $this->expectExceptionMessage('must return a Response');

        $pipeline->handle(new Request(), static fn (): Response => new Response());
    }
}

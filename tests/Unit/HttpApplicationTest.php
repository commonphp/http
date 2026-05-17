<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Tests\Unit;

use CommonPHP\HTTP\Contracts\HttpSurfaceInterface;
use CommonPHP\HTTP\Contracts\RequestFactoryInterface;
use CommonPHP\HTTP\Contracts\ResponseEmitterInterface;
use CommonPHP\HTTP\Contracts\ResponseFactoryInterface;
use CommonPHP\HTTP\HttpApplication;
use CommonPHP\HTTP\MiddlewarePipeline;
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\HTTP\SurfaceRegistry;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;

final class HttpApplicationTest extends TestCase
{
    public function testItRegistersHttpDefinitions(): void
    {
        $surface = new class implements HttpSurfaceInterface {
            public function supports(Request $request): bool
            {
                return $request->path() === '/api/ping';
            }

            public function handle(Request $request): Response
            {
                return new Response('pong');
            }
        };
        $middleware = static fn (Request $request, callable $next): Response => $next($request)->withHeader('X-App', 'yes');
        $app = (new HttpApplication())
            ->surface('api', $surface, '/api', 5)
            ->middleware($middleware);
        $builder = new ContainerBuilder();

        $app->configure($builder);
        $container = $builder->build();

        self::assertInstanceOf(RequestFactoryInterface::class, $container->get(RequestFactoryInterface::class));
        self::assertInstanceOf(ResponseFactoryInterface::class, $container->get(ResponseFactoryInterface::class));
        self::assertInstanceOf(ResponseEmitterInterface::class, $container->get(ResponseEmitterInterface::class));

        $registry = $container->get(SurfaceRegistry::class);
        $pipeline = $container->get(MiddlewarePipeline::class);

        self::assertSame($surface, $registry->find(new Request('GET', '/api/ping')));
        self::assertSame(
            ['api' => ['name' => 'api', 'prefix' => '/api', 'priority' => 5]],
            $registry->entries(),
        );

        $response = $pipeline->handle(new Request(), static fn (): Response => new Response('ok'));
        self::assertSame('yes', $response->header('X-App'));
    }
}

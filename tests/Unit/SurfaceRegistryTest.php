<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Tests\Unit;

use CommonPHP\HTTP\Contracts\HttpSurfaceInterface;
use CommonPHP\HTTP\Exceptions\SurfaceNotFoundException;
use CommonPHP\HTTP\HttpSurfaceResolver;
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\HTTP\SurfaceRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SurfaceRegistryTest extends TestCase
{
    public function testItRegistersFindsAndRemovesSurfaces(): void
    {
        $api = $this->surface('/api', 'api');
        $web = $this->surface('/', 'web');
        $registry = new SurfaceRegistry();

        self::assertSame($registry, $registry->register('web', $web));
        $registry->register('api', $api, 'api', 10);

        self::assertTrue($registry->has('api'));
        self::assertSame($api, $registry->get('api'));
        self::assertSame($api, $registry->find(new Request('GET', '/api/users')));
        self::assertSame($web, $registry->find(new Request('GET', '/about')));
        self::assertSame(['api' => $api, 'web' => $web], $registry->all());
        self::assertSame(
            [
                'api' => ['name' => 'api', 'prefix' => '/api', 'priority' => 10],
                'web' => ['name' => 'web', 'prefix' => '/', 'priority' => 0],
            ],
            $registry->entries(),
        );

        self::assertSame($registry, $registry->remove('api'));
        self::assertFalse($registry->has('api'));
    }

    public function testItUsesPrefixLengthWhenPrioritiesMatch(): void
    {
        $admin = $this->surface('/api/admin', 'admin');
        $api = $this->surface('/api', 'api');
        $registry = (new SurfaceRegistry())
            ->register('api', $api, '/api')
            ->register('admin', $admin, '/api/admin');

        self::assertSame($admin, $registry->find(new Request('GET', '/api/admin/users')));
    }

    public function testResolverHandlesMatchedSurfaces(): void
    {
        $registry = (new SurfaceRegistry())->register('api', $this->surface('/api', 'api'), '/api');
        $response = (new HttpSurfaceResolver($registry))->handle(new Request('GET', '/api/ping'));

        self::assertSame('api', $response->body());
    }

    public function testResolverThrowsWhenNoSurfaceMatches(): void
    {
        $this->expectException(SurfaceNotFoundException::class);
        $this->expectExceptionMessage('No HTTP surface registered');

        (new HttpSurfaceResolver(new SurfaceRegistry()))->resolve(new Request('GET', '/missing'));
    }

    public function testItRejectsMissingSurfaceNames(): void
    {
        $this->expectException(SurfaceNotFoundException::class);

        (new SurfaceRegistry())->get('missing');
    }

    public function testItRejectsEmptyNames(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('HTTP surface name cannot be empty.');

        (new SurfaceRegistry())->register('', $this->surface('/', 'web'));
    }

    private function surface(string $prefix, string $body): HttpSurfaceInterface
    {
        return new class($prefix, $body) implements HttpSurfaceInterface {
            public function __construct(
                private readonly string $prefix,
                private readonly string $body,
            ) {
            }

            public function supports(Request $request): bool
            {
                return $this->prefix === '/'
                    || $request->path() === $this->prefix
                    || str_starts_with($request->path(), $this->prefix . '/');
            }

            public function handle(Request $request): Response
            {
                return new Response($this->body);
            }
        };
    }
}

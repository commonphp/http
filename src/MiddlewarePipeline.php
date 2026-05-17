<?php

declare(strict_types=1);

namespace CommonPHP\HTTP;

use CommonPHP\HTTP\Contracts\MiddlewareInterface;
use CommonPHP\HTTP\Exceptions\MiddlewareException;

class MiddlewarePipeline
{
    /**
     * @var list<MiddlewareInterface|callable>
     */
    private array $middleware = [];

    /**
     * @param iterable<MiddlewareInterface|callable> $middleware
     */
    public function __construct(iterable $middleware = [])
    {
        foreach ($middleware as $entry) {
            $this->pipe($entry);
        }
    }

    public function pipe(MiddlewareInterface|callable $middleware): static
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    /**
     * @return list<MiddlewareInterface|callable>
     */
    public function all(): array
    {
        return $this->middleware;
    }

    public function isEmpty(): bool
    {
        return $this->middleware === [];
    }

    /**
     * @param callable(Request): Response|null $fallback
     */
    public function handle(Request $request, ?callable $fallback = null): Response
    {
        $fallback ??= static fn (): Response => new Response('Not Found', 404, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);

        $next = array_reduce(
            array_reverse($this->middleware),
            fn (callable $next, MiddlewareInterface|callable $middleware): callable => function (Request $request) use ($middleware, $next): Response {
                $response = $middleware instanceof MiddlewareInterface
                    ? $middleware->process($request, $next)
                    : $middleware($request, $next);

                if (!$response instanceof Response) {
                    throw MiddlewareException::invalidResponse(get_debug_type($response));
                }

                return $response;
            },
            $fallback,
        );

        return $next($request);
    }
}

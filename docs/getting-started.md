# Getting Started

CommonPHP HTTP can be used as a standalone HTTP utility package or as the HTTP executive inside a Runtime kernel.

## Create a Request

Use `RequestFactory` when building a request from PHP globals or from explicit values.

```php
use CommonPHP\HTTP\RequestFactory;

$request = (new RequestFactory())->fromGlobals();

if ($request->isJson()) {
    $payload = $request->json();
}
```

For tests and non-global usage, create requests directly:

```php
use CommonPHP\HTTP\RequestFactory;

$request = (new RequestFactory())->create(
    method: 'POST',
    uri: '/api/users?active=1',
    headers: ['Content-Type' => 'application/json'],
    body: '{"name":"Ada"}',
);
```

## Create a Response

`ResponseFactory` covers common response shapes.

```php
use CommonPHP\HTTP\ResponseFactory;

$responses = new ResponseFactory();

return $responses->json(['ok' => true]);
```

Responses are immutable-style objects. Methods such as `withHeader()` return a clone.

```php
$response = $responses
    ->text('Queued', 202)
    ->withHeader('X-Request-Id', $requestId);
```

## Add Middleware

Middleware receives the request and a `$next` callable.

```php
use CommonPHP\HTTP\MiddlewarePipeline;
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;

$pipeline = (new MiddlewarePipeline())->pipe(
    static fn (Request $request, callable $next): Response =>
        $next($request)->withHeader('X-App', 'CommonPHP'),
);
```

## Add a Surface

Surfaces are small request handlers. They let API, assets, docs, and web packages plug into the HTTP layer without the HTTP package knowing their internals.

```php
use CommonPHP\HTTP\Contracts\HttpSurfaceInterface;
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\HTTP\ResponseFactory;

final class HealthSurface implements HttpSurfaceInterface
{
    public function supports(Request $request): bool
    {
        return $request->path() === '/health';
    }

    public function handle(Request $request): Response
    {
        return (new ResponseFactory())->json(['status' => 'ok']);
    }
}
```

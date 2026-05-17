# Middleware

`MiddlewarePipeline` composes request middleware around a final request handler.

Middleware may be a callable:

```php
$pipeline->pipe(
    static function (Request $request, callable $next): Response {
        $response = $next($request);

        return $response->withHeader('X-App', 'CommonPHP');
    },
);
```

or an object implementing `MiddlewareInterface`:

```php
use CommonPHP\HTTP\Contracts\MiddlewareInterface;

final class TraceMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        $traceId = bin2hex(random_bytes(8));

        return $next($request->withAttribute('traceId', $traceId))
            ->withHeader('X-Trace-Id', $traceId);
    }
}
```

## Order

Middleware runs in the order it is piped. The first middleware sees the request first and the response last.

```php
$pipeline = (new MiddlewarePipeline())
    ->pipe($first)
    ->pipe($second);
```

Flow:

```mermaid
flowchart LR
    A["Request"] --> B["First middleware"]
    B --> C["Second middleware"]
    C --> D["Handler or surface"]
    D --> E["Second middleware response phase"]
    E --> F["First middleware response phase"]
    F --> G["Response"]
```

## Return Values

Middleware must return a `Response`. Returning anything else throws `MiddlewareException`.

The default fallback handler returns a plain text `404 Not Found`, but most applications pass `HttpSurfaceResolver::handle()` as the final handler.

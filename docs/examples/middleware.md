# Middleware Example

```php
use CommonPHP\HTTP\MiddlewarePipeline;
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\HTTP\ResponseFactory;

$pipeline = (new MiddlewarePipeline())
    ->pipe(static function (Request $request, callable $next): Response {
        $request = $request->withAttribute('traceId', 'trace-123');

        return $next($request)->withHeader('X-Trace-Id', $request->attribute('traceId'));
    });

$response = $pipeline->handle(
    new Request('GET', '/'),
    static fn (Request $request): Response => (new ResponseFactory())->text(
        'Trace: ' . $request->attribute('traceId'),
    ),
);
```

The final response body is `Trace: trace-123` and the response includes `X-Trace-Id: trace-123`.

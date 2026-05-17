# Usage

The package is centered around a small flow:

1. Build a `Request`.
2. Pass it through `MiddlewarePipeline`.
3. Resolve a matching `HttpSurfaceInterface`.
4. Return a `Response`.
5. Emit the response.

## Manual Dispatch

```php
use CommonPHP\HTTP\HttpSurfaceResolver;
use CommonPHP\HTTP\MiddlewarePipeline;
use CommonPHP\HTTP\NativeResponseEmitter;
use CommonPHP\HTTP\RequestFactory;
use CommonPHP\HTTP\SurfaceRegistry;

$requests = new RequestFactory();
$registry = new SurfaceRegistry();
$pipeline = new MiddlewarePipeline();
$resolver = new HttpSurfaceResolver($registry);
$emitter = new NativeResponseEmitter();

$request = $requests->fromGlobals();
$response = $pipeline->handle(
    $request,
    static fn ($request) => $resolver->handle($request),
);

$emitter->emit($response, $request);
```

## Runtime Dispatch

`HttpApplication` extends the Runtime kernel and sets `HttpExecutive` as its default executive.

```php
use CommonPHP\HTTP\HttpApplication;

(new HttpApplication())
    ->surface('health', new HealthSurface(), '/health')
    ->run();
```

## Direct Objects

You can bypass factories when you need a simple object in tests.

```php
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;

$request = new Request('GET', '/search?q=php');
$response = new Response('Hello', 200, ['Content-Type' => 'text/plain']);
```

Direct construction is intentionally supported because these objects are simple and easy to debug.

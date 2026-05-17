# HTTP Application And Executive

`HttpApplication` is a Runtime kernel configured for HTTP execution.

## HttpApplication

```php
use CommonPHP\HTTP\HttpApplication;

$app = (new HttpApplication())
    ->surface('api', new ApiSurface(), '/api')
    ->middleware(new TraceMiddleware());

$app->run();
```

The application binds:

- `RequestFactoryInterface` to `RequestFactory`
- `ResponseFactoryInterface` to `ResponseFactory`
- `ResponseEmitterInterface` to `NativeResponseEmitter`
- `SurfaceRegistry` with registered application surfaces
- `MiddlewarePipeline` with registered application middleware

## HttpExecutive

`HttpExecutive` performs the runtime request flow:

1. Build a request with `RequestFactoryInterface::fromGlobals()`.
2. Run `MiddlewarePipeline`.
3. Resolve the final surface with `HttpSurfaceResolver`.
4. Emit the response with `ResponseEmitterInterface`.
5. Return a runtime exit status.

HTTP exceptions are converted into HTTP responses. Server error responses return Runtime's exception exit status. Non-server responses return success.

## Native Response Emission

`NativeResponseEmitter` uses PHP's native `http_response_code()`, `header()`, and `echo` behavior. It suppresses bodies for `HEAD` requests and bodyless statuses such as `204` and `304`.

Use a custom `ResponseEmitterInterface` implementation in tests or non-native environments.

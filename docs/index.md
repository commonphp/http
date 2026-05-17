# CommonPHP HTTP Documentation

CommonPHP HTTP is the request/response foundation for web-facing CommonPHP packages. It provides request parsing, response creation, headers, cookies, uploads, middleware, surface resolution, native response emission, and an HTTP executive that can run inside `comphp/runtime`.

HTTP intentionally stops before routing. Route collections, named routes, constraints, controllers, template rendering, API actions, asset lookup, and documentation rendering belong to packages layered above this one.

## Start Here

- [Getting started](getting-started.md)
- [Usage](usage.md)
- [Package boundaries](package-boundaries.md)

## HTTP Concepts

- [Architecture](architecture.md)
- [Requests](requests.md)
- [Responses](responses.md)
- [Headers, cookies, and uploads](headers-cookies-uploads.md)
- [Middleware](middleware.md)
- [Surfaces and resolution](surfaces.md)
- [HTTP application and executive](http-application.md)
- [Error handling](error-handling.md)

## Examples

- [Examples index](examples/index.md)
- [Basic request and response](examples/basic-request-response.md)
- [Middleware](examples/middleware.md)
- [Surface application](examples/surface-application.md)

## Development

- [Testing and QA](testing.md)

## Public API Map

Core request and response classes:

- `CommonPHP\HTTP\Request`
- `CommonPHP\HTTP\RequestFactory`
- `CommonPHP\HTTP\Response`
- `CommonPHP\HTTP\ResponseFactory`
- `CommonPHP\HTTP\HeaderBag`
- `CommonPHP\HTTP\Cookie`
- `CommonPHP\HTTP\UploadedFile`

Dispatch and execution:

- `CommonPHP\HTTP\MiddlewarePipeline`
- `CommonPHP\HTTP\SurfaceRegistry`
- `CommonPHP\HTTP\HttpSurfaceResolver`
- `CommonPHP\HTTP\NativeResponseEmitter`
- `CommonPHP\HTTP\HttpExecutive`
- `CommonPHP\HTTP\HttpApplication`

Contracts:

- `CommonPHP\HTTP\Contracts\HttpSurfaceInterface`
- `CommonPHP\HTTP\Contracts\MiddlewareInterface`
- `CommonPHP\HTTP\Contracts\RequestFactoryInterface`
- `CommonPHP\HTTP\Contracts\ResponseFactoryInterface`
- `CommonPHP\HTTP\Contracts\ResponseEmitterInterface`

Enums:

- `CommonPHP\HTTP\Enums\RequestMethod`
- `CommonPHP\HTTP\Enums\RequestScheme`
- `CommonPHP\HTTP\Enums\ResponseStatus`

Exceptions:

- `CommonPHP\HTTP\Exceptions\HttpException`
- `CommonPHP\HTTP\Exceptions\InvalidHeaderException`
- `CommonPHP\HTTP\Exceptions\InvalidRequestException`
- `CommonPHP\HTTP\Exceptions\MiddlewareException`
- `CommonPHP\HTTP\Exceptions\ResponseEmissionException`
- `CommonPHP\HTTP\Exceptions\SurfaceNotFoundException`
- `CommonPHP\HTTP\Exceptions\UnsupportedMethodException`
- `CommonPHP\HTTP\Exceptions\UnsupportedSchemeException`
- `CommonPHP\HTTP\Exceptions\UploadedFileException`

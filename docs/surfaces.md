# Surfaces And Resolution

Surfaces connect the HTTP package to higher-level packages.

## Surface Contract

```php
use CommonPHP\HTTP\Contracts\HttpSurfaceInterface;

final class ApiSurface implements HttpSurfaceInterface
{
    public function supports(Request $request): bool
    {
        return str_starts_with($request->path(), '/api/');
    }

    public function handle(Request $request): Response
    {
        return new Response('Handled by API');
    }
}
```

`supports()` lets the surface make the final decision. A surface might check the path, method, host, headers, or any other request detail.

## Registry

Register surfaces with a name, path prefix, and optional priority.

```php
$registry = (new SurfaceRegistry())
    ->register('assets', $assetSurface, '/assets', 20)
    ->register('api', $apiSurface, '/api', 10)
    ->register('web', $webSurface, '/', 0);
```

The registry first filters by prefix, then asks the surface whether it supports the request. Higher priority wins. If priorities match, the longer prefix wins.

## Resolver

`HttpSurfaceResolver` resolves and handles the request:

```php
$resolver = new HttpSurfaceResolver($registry);
$response = $resolver->handle($request);
```

If no surface matches, it throws `SurfaceNotFoundException`. `HttpExecutive` converts that exception to a `404 Not Found` response.

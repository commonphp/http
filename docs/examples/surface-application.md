# Surface Application Example

```php
use CommonPHP\HTTP\Contracts\HttpSurfaceInterface;
use CommonPHP\HTTP\HttpApplication;
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

(new HttpApplication())
    ->surface('health', new HealthSurface(), '/health')
    ->run();
```

This lets Runtime build the container, run `HttpExecutive`, pass the request through middleware, resolve the health surface, and emit the response.

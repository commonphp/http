# Requests

`CommonPHP\HTTP\Request` represents one inbound HTTP request.

## Construction

Use `RequestFactory::fromGlobals()` for real HTTP traffic:

```php
$request = (new RequestFactory())->fromGlobals();
```

Use `RequestFactory::create()` or `new Request()` for tests and internal dispatch:

```php
$request = (new RequestFactory())->create(
    method: 'POST',
    uri: '/api/users?active=1',
    headers: ['Content-Type' => 'application/json'],
    body: '{"name":"Ada"}',
);
```

## Common Accessors

```php
$request->method();       // RequestMethod
$request->methodValue();  // GET, POST, PATCH, ...
$request->scheme();       // RequestScheme
$request->isSecure();
$request->uri();
$request->path();
$request->queryString();
$request->target();
$request->fullUri();
$request->host();
$request->ip();
```

## Data Bags

```php
$request->queryParams();
$request->query('page', 1);

$request->cookies();
$request->cookie('session');

$request->files();
$request->file('avatar');

$request->serverParams();
$request->server('REMOTE_ADDR');

$request->attributes();
$request->attribute('route');
```

Attributes are intended for middleware and surfaces. They are not read from globals.

## Headers and Body

```php
$request->hasHeader('Content-Type');
$request->header('Accept');
$request->firstHeader('Accept');
$request->headers();
$request->body();
$request->parsedBody();
```

`RequestFactory::fromGlobals()` parses JSON and URL-encoded request bodies when possible. Invalid JSON throws `InvalidRequestException`.

## Content Negotiation Helpers

```php
$request->isJson();
$request->wantsJson();
$request->accepts('text/html');
$payload = $request->json();
```

`json()` decodes the raw body and throws `InvalidRequestException` when the body is malformed.

## Cloning Helpers

Request mutation methods return clones.

```php
$request = $request
    ->withMethod('PATCH')
    ->withUri('/profile')
    ->withHeader('X-Trace', $traceId)
    ->withAttribute('userId', 42);
```

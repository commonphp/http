# Responses

`CommonPHP\HTTP\Response` represents an outbound HTTP response.

## ResponseFactory

Use `ResponseFactory` for common response types:

```php
$responses = new ResponseFactory();

$responses->text('Hello');
$responses->html('<h1>Hello</h1>');
$responses->json(['ok' => true]);
$responses->redirect('/login');
$responses->noContent();
$responses->notFound('Missing');
```

## Direct Responses

```php
use CommonPHP\HTTP\Response;

$response = new Response(
    body: 'Created',
    status: 201,
    headers: ['Content-Type' => 'text/plain'],
);
```

Status codes may use either `ResponseStatus` enum values or valid integer codes from `100` through `599`.

## Inspecting Responses

```php
$response->statusCode();
$response->status();
$response->reasonPhrase();
$response->headers();
$response->header('Content-Type');
$response->body();
```

Classification helpers:

```php
$response->isInformational();
$response->isSuccessful();
$response->isRedirection();
$response->isClientError();
$response->isServerError();
$response->allowsBody();
```

`allowsBody()` returns false for informational responses and bodyless statuses such as `204`, `205`, and `304`.

## Cloning Helpers

Responses use immutable-style helpers.

```php
$response = $response
    ->withStatus(202)
    ->withHeader('X-Request-Id', $requestId)
    ->withAddedHeader('Vary', 'Accept')
    ->withBody('Queued')
    ->appendBody("\n");
```

Cookie helpers add `Set-Cookie` headers.

```php
$response = $response
    ->withCookie(new Cookie('session', $sessionId, secure: true))
    ->withoutCookie('legacy');
```

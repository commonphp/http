# Error Handling

HTTP exceptions extend `CommonPHP\HTTP\Exceptions\HttpException`.

## Exception Types

- `InvalidHeaderException` for unsafe or malformed header names and values.
- `InvalidRequestException` for malformed requests, including invalid JSON request bodies.
- `MiddlewareException` for middleware failures or invalid middleware return values.
- `ResponseEmissionException` for native response emission failures.
- `SurfaceNotFoundException` when no registered surface can handle a request.
- `UnsupportedMethodException` for unknown HTTP methods.
- `UnsupportedSchemeException` for unsupported request schemes.
- `UploadedFileException` for invalid upload state or failed file moves.

## Executive Mapping

`HttpExecutive` catches `HttpException` and maps it to plain text responses:

- Missing surface: `404 Not Found`
- Unsupported method: `405 Method Not Allowed`
- Invalid request, invalid header, unsupported scheme, upload error: `400 Bad Request`
- Middleware failure and generic HTTP errors: `500 Internal Server Error`

Unexpected exceptions are not swallowed by the HTTP package. Runtime's error handling should record and report them.

## Development Guidance

Throw HTTP exceptions for expected HTTP-layer failures. Let domain, database, rendering, and application errors use their own package exceptions so the owning layer can decide how to report them.

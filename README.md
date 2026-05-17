# CommonPHP HTTP

CommonPHP HTTP provides HTTP request, response, middleware, and web execution support for CommonPHP applications. It defines the HTTP executive and the lower-level pieces needed to receive a request, pass it through middleware, and emit a response.

The package is the HTTP foundation for web-facing CommonPHP packages such as router, API, assets, docs, and web.

## Requirements

- PHP `^8.5`
- `comphp/runtime:^0.3`
- PSR HTTP packages as required by the implementation

## Installation

Once this package is available through your Composer repositories, install it with:

```bash
composer require comphp/http
```

## Usage

```php
<?php

// TODO: Write usage
```

## Package Notes

This package should provide the HTTP executive, request/response abstractions, middleware support, surface resolution, response emission, and HTTP error handling. Routing should remain in `comphp/router`.

## Error Handling

Invalid requests, response emission failures, middleware failures, and unmatched HTTP surfaces should throw CommonPHP HTTP exceptions or produce appropriate HTTP error responses.

## Documentation

- [Documentation index](docs/index.md)
- [Usage](docs/usage.md)
- [Testing](TESTING.md)
- [Contributing](CONTRIBUTING.md)
- [Security](SECURITY.md)

## License

MIT. See [LICENSE.md](LICENSE.md).

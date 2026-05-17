# Package Boundaries

CommonPHP HTTP is the lowest web-facing package. It should stay boring, dependency-light, and easy to inspect.

## Belongs Here

- `Request`, `Response`, and their factories.
- `HeaderBag`, `Cookie`, and `UploadedFile`.
- HTTP method, scheme, and status enums.
- Middleware composition.
- Surface registration and resolution.
- Native response emission.
- Runtime HTTP executive wiring.

## Belongs Elsewhere

- Routing belongs in `comphp/router`.
- API actions and problem formats belong in `comphp/api`.
- Page rendering belongs in `comphp/web` and UI packages.
- Static asset resolution belongs in `comphp/assets`.
- Documentation loading and rendering belongs in `comphp/docs`.
- Session persistence belongs in `comphp/session`.
- Authorization and CSRF belong in `comphp/security`.
- Validation of user input belongs in `comphp/validation`.

## Design Rules

HTTP objects should be simple to construct and easy to dump while debugging. Avoid hidden global reads outside `RequestFactory::fromGlobals()` and `NativeResponseEmitter::emit()`.

Middleware and surfaces should depend on `Request` and `Response`, not on Runtime internals. Runtime integration is useful, but the HTTP package should remain usable without booting a kernel.

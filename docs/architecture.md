# Architecture

CommonPHP HTTP is deliberately small. It provides HTTP primitives and dispatch glue, then hands real application behavior to surfaces and middleware.

## Responsibilities

The package owns:

- Request construction from globals and explicit values.
- Header normalization and validation.
- Cookie and uploaded-file value objects.
- Response creation and native PHP response emission.
- Middleware composition.
- Surface registration and resolution.
- Runtime integration through `HttpApplication` and `HttpExecutive`.

The package does not own:

- Route definitions or route matching.
- Controllers or actions.
- Template rendering.
- API problem details beyond basic JSON response creation.
- Asset lookup or filesystem access.
- Session, authentication, authorization, or CSRF behavior.

## Request Flow

```mermaid
flowchart LR
    A["PHP globals"] --> B["RequestFactory"]
    B --> C["Request"]
    C --> D["MiddlewarePipeline"]
    D --> E["HttpSurfaceResolver"]
    E --> F["HttpSurfaceInterface"]
    F --> G["Response"]
    G --> H["NativeResponseEmitter"]
```

## Surface Boundary

An HTTP surface is the smallest integration point for a web-facing package. API, assets, docs, and web packages can each provide a surface and decide internally how to route or render requests.

`SurfaceRegistry` only asks two questions:

- Does the request path match the registered prefix?
- Does the surface itself support the request?

This keeps the HTTP layer predictable while still allowing richer packages to own their own behavior.

## Error Boundary

`HttpExecutive` catches `HttpException` instances and converts them to plain text HTTP responses. Unexpected non-HTTP exceptions are left for Runtime's error handling layer.

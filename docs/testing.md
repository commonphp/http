# Testing And QA

The HTTP package has a package-local PHPUnit suite.

```bash
vendor/bin/phpunit --configuration package/http/phpunit.xml.dist
```

The suite covers:

- Header normalization, cloning, and validation.
- Cookie rendering and expiration.
- Uploaded file normalization, reading, and moving.
- Request construction, parsing, attributes, JSON, and cloning helpers.
- Response creation, status classification, headers, body helpers, and cookies.
- Request and response factories.
- HTTP method, scheme, and status enums.
- Middleware order and invalid return values.
- Surface registry priority, prefixes, and resolver errors.
- Native response emission body behavior.
- HTTP application container bindings.
- HTTP executive dispatch and HTTP exception mapping.

Run syntax checks when editing source or tests:

```bash
Get-ChildItem -Path package/http/src,package/http/tests -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

Keep tests close to the public API. The package should stay easy to debug, so tests should prefer direct object construction and explicit assertions over deep mocks.

# Headers, Cookies, And Uploads

HTTP has a few small value objects that keep common edge cases in one place.

## HeaderBag

`HeaderBag` stores headers case-insensitively while preserving canonical names.

```php
$headers = new HeaderBag([
    'content-type' => 'application/json',
    'X-Trace' => ['one', 'two'],
]);

$headers->has('Content-Type');
$headers->first('x-trace');
$headers->values('X-Trace');
$headers->all();
```

Header names and values are validated to prevent invalid header output and response splitting.

Use `HeaderBag::fromServer()` when converting PHP server variables:

```php
$headers = HeaderBag::fromServer($_SERVER);
```

## Cookie

`Cookie` renders a safe `Set-Cookie` header value.

```php
$cookie = new Cookie(
    name: 'session',
    value: $sessionId,
    path: '/',
    secure: true,
    httpOnly: true,
    sameSite: 'Lax',
);

$header = $cookie->toHeader();
```

Expire a cookie with:

```php
$response = $response->withoutCookie('session');
```

or:

```php
$expired = Cookie::expired('session');
```

## UploadedFile

`UploadedFile` wraps one uploaded file from PHP's `$_FILES` structure.

```php
$file = UploadedFile::fromArray('avatar', $_FILES['avatar']);

if ($file->isValid()) {
    $file->moveTo($targetPath);
}
```

Nested PHP file arrays can be normalized:

```php
$files = UploadedFile::normalizeArray($_FILES);
$avatar = $files['avatar'] ?? null;
```

Invalid uploads throw `UploadedFileException` when content is read or the file is moved.

# Basic Request And Response

```php
use CommonPHP\HTTP\RequestFactory;
use CommonPHP\HTTP\ResponseFactory;

$requests = new RequestFactory();
$responses = new ResponseFactory();

$request = $requests->create(
    method: 'GET',
    uri: '/hello?name=Ada',
    headers: ['Accept' => 'application/json'],
);

$response = $responses->json([
    'message' => 'Hello ' . $request->query('name', 'friend'),
]);

echo $response->body();
```

Output:

```json
{"message":"Hello Ada"}
```

<?php

declare(strict_types=1);

namespace CommonPHP\HTTP;

use CommonPHP\HTTP\Contracts\ResponseEmitterInterface;
use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Exceptions\ResponseEmissionException;

class NativeResponseEmitter implements ResponseEmitterInterface
{
    public function emit(Response $response, ?Request $request = null): void
    {
        $this->emitHeaders($response);

        if (!$response->allowsBody()) {
            return;
        }

        if ($request !== null && $request->method() === RequestMethod::HEAD) {
            return;
        }

        echo $response->body();
    }

    private function emitHeaders(Response $response): void
    {
        if (headers_sent($file, $line)) {
            throw ResponseEmissionException::headersAlreadySent($file, $line);
        }

        http_response_code($response->statusCode());

        foreach ($response->headers()->all() as $name => $values) {
            $replace = strtolower($name) !== 'set-cookie';

            foreach ($values as $index => $value) {
                header($name . ': ' . $value, $replace && $index === 0, $response->statusCode());
            }
        }
    }
}

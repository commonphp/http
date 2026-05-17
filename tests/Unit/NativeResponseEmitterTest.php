<?php

declare(strict_types=1);

namespace CommonPHP\HTTP\Tests\Unit;

use CommonPHP\HTTP\NativeResponseEmitter;
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use PHPUnit\Framework\TestCase;

final class NativeResponseEmitterTest extends TestCase
{
    protected function setUp(): void
    {
        if (!headers_sent()) {
            header_remove();
        }
    }

    protected function tearDown(): void
    {
        if (!headers_sent()) {
            header_remove();
        }
    }

    public function testItEmitsResponseBodies(): void
    {
        $emitter = new NativeResponseEmitter();
        $response = new Response('hello', 200, ['Content-Type' => 'text/plain']);

        ob_start();
        $emitter->emit($response, new Request('GET', '/'));
        $output = ob_get_clean();

        self::assertSame('hello', $output);
    }

    public function testItSuppressesBodiesForHeadRequests(): void
    {
        $emitter = new NativeResponseEmitter();

        ob_start();
        $emitter->emit(new Response('hidden'), new Request('HEAD', '/'));
        $output = ob_get_clean();

        self::assertSame('', $output);
    }

    public function testItSuppressesBodiesForBodylessStatuses(): void
    {
        $emitter = new NativeResponseEmitter();

        ob_start();
        $emitter->emit(new Response('hidden', 204), new Request('GET', '/'));
        $output = ob_get_clean();

        self::assertSame('', $output);
    }
}

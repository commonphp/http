<?php

declare(strict_types=1);

namespace CommonPHP\HTTP;

use CommonPHP\HTTP\Contracts\RequestFactoryInterface;
use CommonPHP\HTTP\Contracts\ResponseEmitterInterface;
use CommonPHP\HTTP\Contracts\ResponseFactoryInterface;
use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\HTTP\Exceptions\HttpException;
use CommonPHP\HTTP\Exceptions\InvalidHeaderException;
use CommonPHP\HTTP\Exceptions\InvalidRequestException;
use CommonPHP\HTTP\Exceptions\MiddlewareException;
use CommonPHP\HTTP\Exceptions\SurfaceNotFoundException;
use CommonPHP\HTTP\Exceptions\UnsupportedMethodException;
use CommonPHP\HTTP\Exceptions\UnsupportedSchemeException;
use CommonPHP\HTTP\Exceptions\UploadedFileException;
use CommonPHP\Runtime\Contracts\ExecutiveInterface;
use CommonPHP\Runtime\Contracts\ServiceProviderInterface;
use CommonPHP\Runtime\Support\ExitStatus;
use DI\ContainerBuilder;

use function DI\autowire;

final class HttpExecutive implements ExecutiveInterface, ServiceProviderInterface
{
    private RequestFactoryInterface $requests;

    private ResponseEmitterInterface $emitter;

    private ResponseFactoryInterface $responses;

    private MiddlewarePipeline $pipeline;

    private HttpSurfaceResolver $resolver;

    public function __construct(
        ?RequestFactoryInterface $requests = null,
        ?ResponseEmitterInterface $emitter = null,
        ?ResponseFactoryInterface $responses = null,
        ?MiddlewarePipeline $pipeline = null,
        ?HttpSurfaceResolver $resolver = null,
    ) {
        $this->requests = $requests ?? new RequestFactory();
        $this->emitter = $emitter ?? new NativeResponseEmitter();
        $this->responses = $responses ?? new ResponseFactory();
        $this->pipeline = $pipeline ?? new MiddlewarePipeline();
        $this->resolver = $resolver ?? new HttpSurfaceResolver(new SurfaceRegistry());
    }

    public function configure(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([
            RequestFactoryInterface::class => autowire(RequestFactory::class),
            ResponseFactoryInterface::class => autowire(ResponseFactory::class),
            ResponseEmitterInterface::class => autowire(NativeResponseEmitter::class),
            SurfaceRegistry::class => autowire(SurfaceRegistry::class),
            MiddlewarePipeline::class => autowire(MiddlewarePipeline::class),
            HttpSurfaceResolver::class => autowire(HttpSurfaceResolver::class),
        ]);
    }


    public function execute(): int
    {
        try {
            $request = $this->requests->fromGlobals();
            $response = $this->pipeline->handle(
                $request,
                fn (Request $request): Response => $this->resolver->handle($request),
            );
        } catch (HttpException $exception) {
            $response = $this->errorResponse($exception);
        }

        $this->emitter->emit($response, $request ?? null);

        return $response->isServerError() ? ExitStatus::EXCEPTION : ExitStatus::SUCCESS;
    }

    private function errorResponse(HttpException $exception): Response
    {
        $status = match (true) {
            $exception instanceof SurfaceNotFoundException => ResponseStatus::NOT_FOUND,
            $exception instanceof UnsupportedMethodException => ResponseStatus::METHOD_NOT_ALLOWED,
            $exception instanceof InvalidHeaderException,
            $exception instanceof InvalidRequestException,
            $exception instanceof UnsupportedSchemeException,
            $exception instanceof UploadedFileException => ResponseStatus::BAD_REQUEST,
            $exception instanceof MiddlewareException => ResponseStatus::INTERNAL_SERVER_ERROR,
            default => ResponseStatus::INTERNAL_SERVER_ERROR,
        };

        return $this->responses->text($exception->getMessage(), $status);
    }
}

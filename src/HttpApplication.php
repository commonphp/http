<?php

declare(strict_types=1);

namespace CommonPHP\HTTP;

use CommonPHP\HTTP\Contracts\HttpSurfaceInterface;
use CommonPHP\HTTP\Contracts\MiddlewareInterface;
use CommonPHP\HTTP\Contracts\RequestFactoryInterface;
use CommonPHP\HTTP\Contracts\ResponseEmitterInterface;
use CommonPHP\HTTP\Contracts\ResponseFactoryInterface;
use CommonPHP\Runtime\Contracts\ContainerConfiguratorInterface;
use CommonPHP\Runtime\Kernel;
use CommonPHP\Runtime\Support\InitializationContext;
use DI\ContainerBuilder;

use function DI\autowire;

class HttpApplication extends Kernel implements ContainerConfiguratorInterface
{
    /**
     * @var array<string, array{surface: HttpSurfaceInterface, prefix: string, priority: int}>
     */
    private array $surfaces = [];

    /**
     * @var list<MiddlewareInterface|callable>
     */
    private array $middleware = [];

    public function __construct(?InitializationContext $context = null)
    {
        parent::__construct($context);

        $this->setExecutive(HttpExecutive::class);
    }

    public function surface(
        string $name,
        HttpSurfaceInterface $surface,
        string $pathPrefix = '/',
        int $priority = 0,
    ): static {
        $this->surfaces[$name] = [
            'surface' => $surface,
            'prefix' => $pathPrefix,
            'priority' => $priority,
        ];

        return $this;
    }

    public function middleware(MiddlewareInterface|callable $middleware): static
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    public function configure(ContainerBuilder $builder): void
    {
        $surfaces = $this->surfaces;
        $middleware = $this->middleware;

        $builder->addDefinitions([
            RequestFactoryInterface::class => autowire(RequestFactory::class),
            ResponseFactoryInterface::class => autowire(ResponseFactory::class),
            ResponseEmitterInterface::class => autowire(NativeResponseEmitter::class),
            SurfaceRegistry::class => static function () use ($surfaces): SurfaceRegistry {
                $registry = new SurfaceRegistry();

                foreach ($surfaces as $name => $entry) {
                    $registry->register($name, $entry['surface'], $entry['prefix'], $entry['priority']);
                }

                return $registry;
            },
            MiddlewarePipeline::class => static fn (): MiddlewarePipeline => new MiddlewarePipeline($middleware),
        ]);
    }
}

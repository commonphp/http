<?php

declare(strict_types=1);

namespace CommonPHP\HTTP;

use CommonPHP\Config\ConfigProvider;
use CommonPHP\Config\Contracts\ConfigProviderInterface;
use CommonPHP\Runtime\Contracts\ExecutiveInterface;
use CommonPHP\Runtime\Contracts\ServiceProviderInterface;
use DI\ContainerBuilder;
use function DI\autowire;

final class HttpExecutive implements ExecutiveInterface, ServiceProviderInterface
{

    public function configure(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([
            ConfigProviderInterface::class => autowire(ConfigProvider::class),
        ]);
    }


    public function execute(): int
    {
        return 1;
    }
}
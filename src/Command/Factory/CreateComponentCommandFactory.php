<?php

namespace Contenir\Cli\Tool\Command\Factory;

use Contenir\Cli\Tool\Command\CreateComponentCommand;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class CreateComponentCommandFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): CreateComponentCommand {
        return new CreateComponentCommand();
    }
}

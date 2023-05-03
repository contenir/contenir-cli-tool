<?php

namespace Contenir\Cli\Tool\Command\Factory;

use Contenir\Cli\Tool\Command\CreateModelCommand;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class CreateModelCommandFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): CreateModelCommand {
        return new CreateModelCommand();
    }
}

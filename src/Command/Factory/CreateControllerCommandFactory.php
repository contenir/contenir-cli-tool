<?php

namespace Contenir\Cli\Tool\Command\Factory;

use Contenir\Cli\Tool\Command\CreateControllerCommand;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class CreateControllerCommandFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): CreateControllerCommand {
        return new CreateControllerCommand();
    }
}

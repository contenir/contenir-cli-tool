<?php

namespace Contenir\Cli\Tool\Command\Factory;

use Contenir\Cli\Tool\Command\CreateFormCommand;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class CreateFormCommandFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): CreateFormCommand {
        return new CreateFormCommand();
    }
}

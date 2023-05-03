<?php

namespace Contenir\Cli\Tool\Command\Factory;

use Contenir\Cli\Tool\Command\CreateWorkflowCommand;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class CreateWorkflowCommandFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): CreateWorkflowCommand {
        return new CreateWorkflowCommand();
    }
}

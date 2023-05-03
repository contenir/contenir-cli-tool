<?php

namespace Contenir\Cli\Tool;

class ConfigProvider
{
    /**
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
        ];
    }

    /**
     *
     * @return array
     */
    public function getDependencyConfig()
    {
        return [
            'aliases'   => [],
            'factories' => [
            	Command\CreateControllerCommand::class => Command\Factory\CreateControllerCommandFactory::class,
            	Command\CreateComponentCommand::class => Command\Factory\CreateComponentCommandFactory::class,
            	Command\CreateFormCommand::class => Command\Factory\CreateFormCommandFactory::class,
            	Command\CreateModelCommand::class => Command\Factory\CreateModelCommandFactory::class,
            	Command\CreateWorkflowCommand::class => Command\Factory\CreateWorkflowCommandFactory::class,
            ]
        ];
    }

    /**
     *
     * @return array
     */
    public function getCliConfig()
    {
        return [
			'commands' => [
				'create:controller' => Command\CreateControllerCommand::class,
				'create:component' => Command\CreateComponentCommand::class,
				'create:model' => Command\CreateModelCommand::class,
				'create:form' => Command\CreateFormCommand::class,
				'create:workflow' => Command\CreateWorkflowCommand::class,
			]
        ];
    }
}

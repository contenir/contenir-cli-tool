<?php

declare(strict_types=1);

namespace Contenir\Cli\Tool\Command;

use Generator as GlobalGenerator;
use Laminas\Code\Generator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CreateWorkflowCommand extends CreateComponentCommand
{
    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->addArgument('name', InputArgument::REQUIRED, 'Name of Component')
            ->addOption('module', 'm', InputOption::VALUE_REQUIRED, 'Name of Module')
            ->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'Path to application root')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force creation');
    }

    protected function setParameters()
    {
        $this->setComponentTypeName(
            'Workflow',
            $this->input->getArgument('name'),
        );

        $this->setModuleName($this->input->getOption('module') ?? 'Application');

        if (! $this->setApplicationPath($this->input->getOption('path') ?? '.')) {
            return self::FAILURE;
        }

        $this->setForceCreation($this->input->getOption('force'));
    }

    protected function createComponentUses(): array
    {
        return [
            'Controller' => 'Application\\Controller\\NewsController',
            'Contenir\\Mvc\\Workflow\\Workflow\\ArticleWorkflow'
        ];
    }

    protected function createComponentExtendedClass(): ?string
    {
        return 'Contenir\\Mvc\\Workflow\\Workflow\\ArticleWorkflow';
    }

    protected function createComponentProperties(): array
    {
        return [
            (new Generator\PropertyGenerator())
                ->setName('controller')
                ->setDefaultValue('Controller::class', Generator\PropertyValueGenerator::TYPE_CONSTANT)
                ->setFlags(Generator\PropertyGenerator::FLAG_PROTECTED),
            ['changeFrequency', 'weekly', Generator\PropertyGenerator::FLAG_PROTECTED],
            ['priority', '1.0', Generator\PropertyGenerator::FLAG_PROTECTED]
        ];
    }
}

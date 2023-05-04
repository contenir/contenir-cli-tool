<?php

declare(strict_types=1);

namespace Contenir\Cli\Tool\Command;

use Laminas\Code\Generator;
use Laminas\Filter\Word\CamelCaseToDash;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateControllerCommand extends CreateComponentCommand
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
            'Controller',
            $this->input->getArgument('name'),
        );

        $this->setModuleName($this->input->getOption('module') ?? 'Application');

        if (! $this->setApplicationPath($this->input->getOption('path') ?? '.')) {
            return self::FAILURE;
        }

        $this->setForceCreation($this->input->getOption('force'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($result = parent::execute($input, $output) === self::FAILURE) {
            return self::FAILURE;
        }

        $filter     = new CamelCaseToDash();
        $viewfolder = strtolower($filter->filter($this->moduleName));
        $dir        = $this->applicationPath . "/module/$this->moduleName/view/$viewfolder/" . strtolower($filter->filter($this->componentName));

        if (! file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $phtmlPath = $dir . "/index.phtml";
        file_put_contents($phtmlPath, 'Action "index", controller "' . $this->componentName . '", module "' . $this->moduleName . '".');

        $this->output->writeln(
            sprintf(
                '<info>The controller %s has been created in module %s.</info>',
                $this->getComponentName(),
                $this->moduleName
            )
        );

        return self::SUCCESS;
    }

    protected function createComponentUses(): array
    {
        return [
            'Laminas\\Mvc\\Controller\\AbstractActionController',
            'Laminas\\View\\Model\\ViewModel'
        ];
    }

    protected function createComponentExtendedClass(): ?string
    {
        return 'Laminas\\Mvc\\Controller\\AbstractActionController';
    }

    protected function createComponentProperties(): array
    {
        return [];
    }

    protected function createComponentMethods(): array
    {
        return [
            new Generator\MethodGenerator(
                'indexAction',
                [],
                Generator\MethodGenerator::FLAG_PUBLIC,
                'return new ViewModel([]);'
            ),
        ];
    }
}

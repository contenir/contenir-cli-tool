<?php

declare(strict_types=1);

namespace Contenir\Cli\Tool\Command;

use Laminas\Code\Generator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CreateFormCommand extends CreateComponentCommand
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
            'Form',
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
            'Laminas\\Form\\Form',
            'Laminas\\Form\\Fieldset',
            'Laminas\\InputFilter',
            'Laminas\\Validator'
        ];
    }

    protected function createComponentExtendedClass(): ?string
    {
        return 'Laminas\\Form\\Form';
    }

    protected function createComponentInterfaces(): array
    {
        return[
            'Laminas\\InputFilter\\InputFilterProviderInterface'
        ];
    }

    protected function createComponentMethods(): array
    {
        return [
            (new Generator\MethodGenerator(
                'init',
                [],
                Generator\MethodGenerator::FLAG_PUBLIC,
                <<<END
\$this->add([
    'type'    => 'csrf',
    'name'    => 'csrf',
    'options' => [
        'csrf_options' => [
            'timeout' => 600
        ]
    ],
]);

\$this->add([
    'type'    => 'button',
    'name'    => '_submit',
    'options' => [
        'label' => 'Sign In'
    ],
    'attributes' => [
        'type' => 'submit',
    ],
]);
END
            )),
            (new Generator\MethodGenerator(
                'getInputFilterSpecification',
                [],
                Generator\MethodGenerator::FLAG_PUBLIC,
                sprintf("return [];", $this->getComponentName(false))
            )),
        ];
    }
}

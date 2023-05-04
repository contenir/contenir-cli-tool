<?php

declare(strict_types=1);

namespace Contenir\Cli\Tool\Command;

use Laminas\Cli\Command\AbstractParamAwareCommand;
use Laminas\Code\Generator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateComponentCommand extends AbstractParamAwareCommand
{
    protected $input;
    protected $output;
    protected $applicationPath;
    protected $moduleName;
    protected $componentType;
    protected $componentName;
    protected $forceCreation = false;

    protected static $defaultName = 'component';

    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->addArgument('type', InputArgument::REQUIRED, 'Type of Component')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of Component')
            ->addOption('module', 'm', InputOption::VALUE_REQUIRED, 'Name of Module')
            ->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'Path to application root')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force creation');
    }

    protected function setInputOutput(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;
    }

    protected function setParameters()
    {
        $this->setComponentTypeName(
            $this->input->getArgument('type'),
            $this->input->getArgument('name'),
        );

        $this->setModuleName($this->input->getOption('module') ?? 'Application');

        if (! $this->setApplicationPath($this->input->getOption('path') ?? '.')) {
            return self::FAILURE;
        }

        $this->setForceCreation($this->input->getOption('force'));
    }

    protected function setForceCreation($forceCreation = false)
    {
        $this->forceCreation = (bool) $forceCreation;
    }

    protected function setComponentTypeName(string $componentType, string $componentName)
    {
        $this->componentType = ucwords($componentType);
        $this->componentName = ucwords($componentName);
    }

    protected function setApplicationPath(string $applicationPath)
    {
        $this->applicationPath = realpath($applicationPath);

        if (! file_exists("$this->applicationPath/module") || ! file_exists("$this->applicationPath/config/application.config.php")) {
            $this->output->writeln(
                sprintf(
                    '<error>Aborted:</error> The path <options=bold>%s</> doesn\'t contain a Laminas application. I cannot create a module here.',
                    "$this->applicationPath/module"
                ),
                OutputInterface::VERBOSITY_VERBOSE
            );
            return false;
        }

        if (! file_exists("{$this->applicationPath}/module/{$this->moduleName}") || ! file_exists("{$this->applicationPath}/module/{$this->moduleName}/src")) {
            $this->output->writeln(
                sprintf(
                    '<error>Aborted:</error> The module <options=bold>%s</> does not exist and no src folder exists.',
                    $this->moduleName
                ),
                OutputInterface::VERBOSITY_VERBOSE
            );
            return false;
        }

        return true;
    }

    protected function setModuleName(string $moduleName)
    {
        $this->moduleName = ucwords($moduleName);
    }

    public function getComponentName(bool $isFactory = false, bool $fqdn = false)
    {
        $componentName = [$this->componentName, $this->componentType];
        if ($isFactory) {
            $componentName[] = 'Factory';
        }

        $componentName = join('', $componentName);

        if ($fqdn) {
            $componentName = $this->getComponentNamespace($isFactory) . '\\' . $componentName;
        }

        return $componentName;
    }

    public function getComponentNamespace(bool $isFactory = false)
    {
        $componentNamespace = [$this->moduleName, $this->componentType];
        if ($isFactory) {
            $componentNamespace[] = 'Factory';
        }

        return join('\\', $componentNamespace);
    }

    public function getComponentPath(bool $isFactory = false)
    {
        $componentPath = array_filter([
            $this->applicationPath,
            'module',
            $this->moduleName,
            'src',
            $this->componentType,
            ($isFactory) ? 'Factory' : null,
            $this->getComponentName($isFactory)
        ]);

        $componentPath = join('/', $componentPath) . '.php';

        return $componentPath;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setInputOutput($input, $output);
        $this->setParameters();

        if (! $this->createComponent()) {
            return self::FAILURE;
        }

        if (! $this->createComponentFactory()) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function checkWriteComponentFile($path, $contents): bool
    {
        $result = (file_put_contents($path, $contents));

        if ($result === false) {
            $this->output->writeln(
                sprintf(
                    '<error>Aborted:</error> There was an error during creation of %s.',
                    $path
                )
            );
            return false;
        }

        return true;
    }

    protected function checkFileAlreadyExists($filePath): bool
    {
        if (file_exists($filePath) && $this->forceCreation === false) {
            $this->output->writeln(
                sprintf(
                    '<error>Aborted:</error> The file <options=bold>%s</> already exists.',
                    $filePath
                )
            );
            return false;
        }

        return true;
    }

    protected function createComponent(): bool
    {
        $componentPath = $this->getComponentPath();

        /*
         * Create Component
         */
        if (! $this->checkFileAlreadyExists($componentPath)) {
            return self::FAILURE;
        }

        $classGenerator = $this->createComponentClass();
        $fileGenerator  = $this->createComponentFile($classGenerator);
        $result         = $this->createComponentDefinition($fileGenerator);

        if (! $this->checkWriteComponentFile($componentPath, $result)) {
            return self::FAILURE;
        }

        return true;
    }

    protected function createComponentFactory(): bool
    {
        /*
         * Create Component Factory
         */

        $componentFactoryPath = $this->getComponentPath(true);

        if (! $this->checkFileAlreadyExists($componentFactoryPath)) {
            return self::FAILURE;
        }

        $classGenerator = $this->createComponentFactoryClass();
        $fileGenerator  = $this->createComponentFile($classGenerator);
        $result         = $this->createComponentDefinition($fileGenerator);

        if (! $this->checkWriteComponentFile($componentFactoryPath, $result)) {
            return self::FAILURE;
        }

        return true;
    }

    protected function createComponentClass(): Generator\ClassGenerator
    {
        $classGenerator = new Generator\ClassGenerator();
        $classGenerator
            ->setNamespaceName($this->getComponentNamespace(false))
            ->setName($this->getComponentName(false, true))
            ->setExtendedClass($this->createComponentExtendedClass())
            ->addProperties($this->createComponentProperties())
            ->addMethods($this->createComponentMethods())
            ->setImplementedInterfaces($this->createComponentInterfaces());

        foreach ($this->createComponentUses() as $alias => $use) {
            if (! is_int($alias)) {
                $classGenerator->addUse($use, $alias);
            } else {
                $classGenerator->addUse($use);
            }
        }

        return $classGenerator;
    }

    protected function createComponentUses(): array
    {
        return [
        ];
    }

    protected function createComponentInterfaces(): array
    {
        return[
        ];
    }

    protected function createComponentExtendedClass(): ?string
    {
        return null;
    }

    protected function createComponentProperties(): array
    {
        return [];
    }

    protected function createComponentMethods(): array
    {
        return [];
    }

    protected function createComponentFactoryClass()
    {
        $classGenerator = new Generator\ClassGenerator();
        $classGenerator->setNamespaceName($this->getComponentNamespace(true));

        $classGenerator
            ->setName($this->getComponentName(true))
            ->addProperties($this->createComponentFactoryProperties())
            ->addMethods($this->createComponentFactoryMethods())
            ->setImplementedInterfaces($this->createComponentFactoryInterfaces());

        foreach ($this->createComponentFactoryUses() as $alias => $use) {
            if (! is_int($alias)) {
                $classGenerator->addUse($use, $alias);
            } else {
                $classGenerator->addUse($use);
            }
        }

        return $classGenerator;
    }

    protected function createComponentFactoryUses()
    {
        return [
            $this->getComponentName(false, true),
            'Psr\\Container\\ContainerInterface',
            'Laminas\\ServiceManager\\Factory\\FactoryInterface'
        ];
    }

    protected function createComponentFactoryInterfaces(): array
    {
        return[
            'Laminas\\ServiceManager\\Factory\\FactoryInterface'
        ];
    }

    protected function createComponentFactoryProperties(): array
    {
        return [];
    }

    protected function createComponentFactoryMethods(): array
    {
        return [
            (new Generator\MethodGenerator(
                '__invoke',
                [
                    new Generator\ParameterGenerator('container', 'Psr\\Container\\ContainerInterface'),
                    (new Generator\ParameterGenerator())->setName('requestedName'),
                    (new Generator\ParameterGenerator())->setName('options')->setType('array')->setDefaultValue(null)
                ],
                Generator\MethodGenerator::FLAG_PUBLIC,
                sprintf("\$config = \$container->get('config');\n\nreturn new %s();", $this->getComponentName(false))
            ))->setReturnType($this->getComponentName(false, true)),
        ];
    }

    protected function createComponentFile(Generator\ClassGenerator $classGenerator): Generator\FileGenerator
    {
        $fileGenerator = new Generator\FileGenerator(
            [
                'classes' => [$classGenerator],
            ]
        );

        return $fileGenerator;
    }

    protected function createComponentDefinition(Generator\FileGenerator $fileGenerator): string
    {
        $uses = [];
        foreach ($fileGenerator->getClasses() as $class) {
            $uses = array_merge($uses, $class->getUses());
        }

        $aliases = [];
        foreach ($uses as $use) {
            $parts                   = explode('\\', $use);
            $className               = array_pop($parts);
            $fqdnClassName           = '\\' . $use;
            $aliases[$fqdnClassName] = $className;
        }

        $generatedContent = $fileGenerator->generate();
        $generatedContent = str_replace(array_keys($aliases), array_values($aliases), $generatedContent);

        return $generatedContent;
    }
}

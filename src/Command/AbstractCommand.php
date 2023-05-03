<?php

declare(strict_types=1);

namespace Contenir\Cli\Tool\Command;

use Psr\Container\ContainerInterface;
use Laminas\Cli\Command\AbstractParamAwareCommand;
use Laminas\Cli\Input\ParamAwareInputInterface;
use Laminas\Code\Generator;
use Laminas\Code\Reflection;
use Laminas\Filter\Word\CamelCaseToDash as CamelCaseToDashFilter;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\Exception\RuntimeException;
use Laminas\View\Model\ViewModel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends AbstractParamAwareCommand
{
    protected $input;
    protected $output;

    protected static $defaultName = 'import';

    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->addArgument('name', InputArgument::REQUIRED, 'Name of Controller')
            ->addOption('module', 'm', InputOption::VALUE_REQUIRED, 'Name of Module')
            ->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'Path to application root')
            ->addOption('force', null, InputOption::VALUE_OPTIONAL, 'Force creation', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $name = $this->input->getArgument('name');
        $module = $this->input->getOption('module') ?? 'Application';
        $path = $this->input->getOption('path') ?? '.';
        $force = ($this->input->getOption('force') !== false) ? true : false;

        if (! file_exists("$path/module") || ! file_exists("$path/config/application.config.php")) {
            $this->output->writeln(
                sprintf(
                    '<error>Aborted:</error> The path <options=bold>%s</> doesn\'t contain a Laminas application. I cannot create a module here.',
                    "$path/module"
                ),
                OutputInterface::VERBOSITY_VERBOSE
            );
            return self::FAILURE;
        }

        /*
         * Generate Controller Class
         */
        $ucName     = ucfirst($name);
        $controllerPath   = $path . '/module/' . $module . '/src/Controller/' . $ucName . 'Controller.php';
        $controller = $ucName . 'Controller';

        if (file_exists($controllerPath) && $force === false) {
            $this->output->writeln(
                sprintf(
                    '<error>Aborted:</error> The controller <options=bold>%s</> already exists in module <options=bold>%s</>.',
                    $name,
                    $module
                )
            );
            return self::FAILURE;
        }

        $code = new Generator\ClassGenerator();
        $code->setNamespaceName(ucfirst($module) . '\Controller')
             ->addUse('Laminas\Mvc\Controller\AbstractActionController')
             ->addUse('Laminas\View\Model\ViewModel');

        $code->setName($controller)
             ->addMethods([
                new Generator\MethodGenerator(
                    'indexAction',
                    [],
                    Generator\MethodGenerator::FLAG_PUBLIC,
                    'return new ViewModel();'
                ),
             ])
             ->setExtendedClass('Laminas\Mvc\Controller\AbstractActionController');

        $file = new Generator\FileGenerator(
            [
                'classes'  => [$code],
            ]
        );

        $result = (file_put_contents($controllerPath, $file->generate()));
        if ($result === false) {
            $this->output->writeln(
                sprintf(
                    '<error>Aborted:</error> There was an error during controller creation.'
                )
            );
            return self::FAILURE;
        }

        /*
         * Generate Controller Factory Class
         */
        $factoryPath   = $path . '/module/' . $module . '/src/Controller/Factory/' . $ucName . 'ControllerFactory.php';
        $factoryClass = $ucName . 'ControllerFactory';
        $controllerClass = ucfirst($module) . '\\Controller\\' . $controller;

        $code = new Generator\ClassGenerator();
        $code->setNamespaceName(ucfirst($module) . '\\Controller\Factory')
             ->addUse($controllerClass)
             ->addUse('Psr\\Container\\ContainerInterface')
             ->addUse('Laminas\\ServiceManager\\Factory\\FactoryInterface');

        $code->setName($factoryClass)
             ->addMethodFromGenerator(
                (new Generator\MethodGenerator(
                    '__invoke',
                    [
                        new Generator\ParameterGenerator('container', '\Psr\\Container\\ContainerInterface'),
                        (new Generator\ParameterGenerator())->setName('requestedName'),
                        (new Generator\ParameterGenerator())->setName('options')->setType('array')->setDefaultValue(null)
                    ],
                    Generator\MethodGenerator::FLAG_PUBLIC,
                    'return new $requestedName();'
                ))->setReturnType($controllerClass),
             )
             ->setImplementedInterfaces([
             	'Laminas\\ServiceManager\\Factory\\FactoryInterface'
             ]);

        $file = new Generator\FileGenerator(
            [
                'classes'  => [$code],
            ]
        );

        $result = (file_put_contents($factoryPath, $file->generate()));
        if ($result === false) {
            $this->output->writeln(
                sprintf(
                    '<error>Aborted:</error> There was an error during controller factory creation.'
                )
            );
            return self::FAILURE;
        }

        /*
         * Generate Controller view and enclosing folder
         */
        $filter = new CamelCaseToDashFilter();
        $viewfolder = strtolower($filter->filter($module));

        $dir = $path . "/module/$module/view/$viewfolder/" . strtolower($filter->filter($name));
        if (! file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $phtml = false;
        $phtmlPath = $dir . "/index.phtml";
        if (file_put_contents($phtmlPath, 'Action "index", controller "' . $ucName . '", module "' . $module . '".')) {
            $phtml = true;
        }

        $this->output->writeln(
            sprintf(
                '<info>The controller %s has been created in module %s.</info>',
                $name,
                $module
            )
        );

        return self::SUCCESS;
    }
}

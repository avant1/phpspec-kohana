<?php

namespace PhpSpec\Kohana;

use PhpSpec\Extension\ExtensionInterface;
use PhpSpec\Kohana\Generator\KohanaCodeGenerator;
use PhpSpec\Kohana\Generator\KohanaSpecificationGenerator;
use PhpSpec\Kohana\Locator\PSR0Locator;
use PhpSpec\ServiceContainer;
use PhpSpec\Util\Filesystem;

class Extension implements ExtensionInterface
{

    /** @var Filesystem */
    private $filesystem;

    public function __construct(Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * @param ServiceContainer $container
     */
    public function load(ServiceContainer $container)
    {
        $documentRoot = $container->getParam('document_root');
        $this->doKohanaSpecificStuff($documentRoot);

        $container->addConfigurator(function(ServiceContainer $c) {
            $c->setShared('locator.locators.kohana_locator',
                function(ServiceContainer $c) {
                    $documentRoot = $c->getParam('document_root');
                    $applicationRoot = $documentRoot . '/application/';

                    return new PSR0Locator(null, null, $applicationRoot . '/classes/', $applicationRoot . '/spec/');
                }
            );

            /** @noinspection PhpUndefinedClassInspection */
            foreach (\Kohana::modules() as $moduleName => $modulePath) {
                $serviceName = sprintf('locator.locators.kohana_module_%s_locator', $moduleName);
                if (!$this->filesystem->isDirectory($modulePath . 'spec/')) {
                    $this->filesystem->makeDirectory($modulePath . 'spec/');
                }

                $c->setShared($serviceName,
                    function() use ($modulePath) {

                        return new PSR0Locator(null, null, $modulePath . '/classes/', $modulePath . 'spec/');
                    }
                );
            }
        });

        $container->setShared('code_generator.generators.kohana_class', function (ServiceContainer $c) {
            return new KohanaCodeGenerator(
                $c->get('console.io'),
                $c->get('code_generator.templates')
            );
        });

        $container->setShared('code_generator.generators.kohana_specification', function (ServiceContainer $c) {
            return new KohanaSpecificationGenerator(
                $c->get('console.io'),
                $c->get('code_generator.templates')
            );
        });
    }

    private function doKohanaSpecificStuff($documentRoot)
    {
        $application = 'application';
        $modules = 'modules';
        $system = 'system';
        define('EXT', '.php');

        define('DOCROOT', realpath(dirname($documentRoot)).DIRECTORY_SEPARATOR);
        if ( ! is_dir($application) AND is_dir(DOCROOT.$application)) {
            $application = DOCROOT.$application;
        }

        if ( ! is_dir($modules) AND is_dir(DOCROOT.$modules)) {
            $modules = DOCROOT.$modules;
        }

        // Make the system relative to the docroot, for symlink'd index.php
        if ( ! is_dir($system) AND is_dir(DOCROOT.$system)) {
            $system = DOCROOT.$system;
        }

        // Define the absolute paths for configured directories
        define('APPPATH', realpath($application).DIRECTORY_SEPARATOR);
        define('MODPATH', realpath($modules).DIRECTORY_SEPARATOR);
        define('SYSPATH', realpath($system).DIRECTORY_SEPARATOR);

        if ( ! defined('KOHANA_ENV'))
        {
            define('KOHANA_ENV', 'DEVELOPMENT');
        }

        // Bootstrap the application
        require APPPATH.'bootstrap'.EXT;

        /** @noinspection PhpUndefinedClassInspection */
        \Kohana::$errors = false;

    }

}

<?php

namespace PhpSpec\Kohana;

use PhpSpec\Extension\ExtensionInterface;
use PhpSpec\Kohana\Autoloader\SimplePSR0LowercaseAutoloader;
use PhpSpec\Kohana\Generator\KohanaCodeGenerator;
use PhpSpec\Kohana\Generator\KohanaGenerator;
use PhpSpec\Kohana\Generator\KohanaSpecificationGenerator;
use PhpSpec\Kohana\Locator\PSR0Locator;
use PhpSpec\ServiceContainer;

class Extension implements ExtensionInterface
{

    /**
     * @param ServiceContainer $container
     */
    public function load(ServiceContainer $container)
    {
		$this->enableSPR0LowercaseAutoloaderIfNeeded($container);

		$this->defineSyspathConstantIfNotDefined($container);

        $container->addConfigurator(function(ServiceContainer $c) {
            $c->setShared('locator.locators.kohana_locator',
                function(ServiceContainer $c) {
                    $applicationRoot = $c->getParam('application_root');
                    return new PSR0Locator(null, null, $applicationRoot . '/classes/', $applicationRoot . '/spec/');
                }
            );
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

	private function enableSPR0LowercaseAutoloaderIfNeeded(ServiceContainer $container)
	{
		if (!$container->getParam('enable_psr0_lowercase_autoloader')) {

			return;
		}

		$applicationRoot = $container->getParam('application_root');
		$classesDirectory = $applicationRoot . '/classes/';
		$autoloader = new SimplePSR0LowercaseAutoloader($classesDirectory);

		spl_autoload_register(array($autoloader, 'loadClass'));
	}

	/**
	 * SYSPATH constant is needed to handle default Kohana file templates,
	 * with "defined('SYSPATH') OR die('No direct script access.');" at the beginning.
	 *
	 * @param ServiceContainer $container
	 */
	private function defineSyspathConstantIfNotDefined(ServiceContainer $container)
	{
		if (defined('SYSPATH')) {

			return;
		}

		$applicationRoot = $container->getParam('application_root');
		$syspath = realpath($applicationRoot . '/../system/');
		define('SYSPATH', $syspath);
	}

}
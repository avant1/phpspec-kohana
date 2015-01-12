<?php

namespace PhpSpec\Kohana\Autoloader;

use PhpSpec\Kohana\Util\Filesystem;

class SimplePSR0LowercaseAutoloader
{

	private $filesystem;
	private $applicationDir;
	private $modulesDir;
	private $systemDir;

	private $classesSubdirectory = '/classes/';

	public function __construct(Filesystem $filesystem, $applicationDir, $modulesDir, $systemDir)
	{
		$this->filesystem = $filesystem;
		$this->applicationDir = $applicationDir;
		$this->modulesDir = $modulesDir;
		$this->systemDir = $systemDir;
	}

	public function loadClass($classname)
	{
		$fileName = $this->convertClassNameToFileName($classname);
		$possibleFileLocations = array();

		$possibleFileLocations[] = $this->getClassFilePathInApplicationDir($fileName);
		$possibleFileLocations[] = $this->getClassFilePathInSystemDir($fileName);
		$possibleFileLocations = array_merge($possibleFileLocations, $this->getClassFilePathesInModulesDir($fileName));

		foreach ($possibleFileLocations as $file) {

			if (is_file($file)) {

				require $file;

				break;
			}
		}
	}

	private function getClassFilePathInApplicationDir($fileName)
	{
		return $this->applicationDir . $this->classesSubdirectory . $fileName;
	}

	private function getClassFilePathInSystemDir($fileName)
	{
		return $this->systemDir . $this->classesSubdirectory . $fileName;
	}

	private function getClassFilePathesInModulesDir($fileName)
	{
		$modules = $this->filesystem->getSubdirectories($this->modulesDir);

		$result = array();
		foreach ($modules as $moduleDir) {
			$result[] = $moduleDir . $this->classesSubdirectory . $fileName;
		}

		return $result;
	}

	private function convertClassNameToFileName($classname)
	{
		return str_replace('_', DIRECTORY_SEPARATOR, strtolower($classname)) . '.php';
	}

}

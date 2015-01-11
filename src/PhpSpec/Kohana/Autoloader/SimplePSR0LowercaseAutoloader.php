<?php

namespace PhpSpec\Kohana\Autoloader;

class SimplePSR0LowercaseAutoloader
{

	private $classesDirectory;

	public function __construct($classesDirectory)
	{
		$this->classesDirectory = $classesDirectory;
	}

	public function loadClass($classname)
	{
		$file = $this->classesDirectory . str_replace('_', DIRECTORY_SEPARATOR, strtolower($classname)) . '.php';

		if (is_file($file)) {
			require $file;
		}
	}


}
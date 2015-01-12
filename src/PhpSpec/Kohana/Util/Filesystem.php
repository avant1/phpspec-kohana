<?php


namespace PhpSpec\Kohana\Util;


class Filesystem
{

	public function getSubdirectories($directory)
	{
		$realDirectoryPath = realpath($directory);

		if (!$realDirectoryPath || !is_dir($realDirectoryPath)) {
			$message = sprintf('"%s" seems to be not a directory.', $directory);
			throw new \Exception($message);
		}


		if (mb_substr($realDirectoryPath, -1, 1, 'utf-8') !== DIRECTORY_SEPARATOR) {
			$realDirectoryPath .= DIRECTORY_SEPARATOR;
		}

		$dirs = glob($realDirectoryPath . '*', GLOB_ONLYDIR);

		return $dirs;
	}


}

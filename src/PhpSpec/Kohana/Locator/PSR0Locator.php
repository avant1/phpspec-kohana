<?php

namespace PhpSpec\Kohana\Locator;

use PhpSpec\Exception\Exception;
use PhpSpec\Locator\ResourceInterface;
use PhpSpec\Locator\ResourceLocatorInterface;
use PhpSpec\Util\Filesystem;

class PSR0Locator implements ResourceLocatorInterface
{
    /**
     * @var string
     */
    private $srcNamespace;

    /**
     * @var string
     */
    private $specSubNamespace;

    /**
     * @var string
     */
    private $srcPath;

    /**
     * @var array
     */
    private $specPath;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string Class to be spec'd
     */
    private $specifiedClass;

    /**
     * @param string $srcNamespace
     * @param string $specSubNamespace
     * @param string $srcPath
     * @param string $specPath
     * @param Filesystem $filesystem
     */
    public function __construct($srcNamespace = '', $specSubNamespace = 'Spec', $srcPath, $specPath, Filesystem $filesystem = null)
    {
        $this->srcNamespace = $srcNamespace;
        $this->specSubNamespace = $specSubNamespace;
        $this->srcPath = rtrim(realpath($srcPath), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $this->specPath = $specPath;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * @return ResourceInterface[]
     */
    public function getAllResources()
    {
        $fullSpecPath = $this->getFullSpecPath();

        $resources = $this->createResourcesFromSpecFiles($fullSpecPath);

        return $resources;
    }

    /**
     * @param string $query
     *
     * @return bool
     *
     * @throws Exception
     */
    public function supportsQuery($query)
    {
        $fullPath = realpath($query);

        if (is_file($fullPath) && '.php' !== substr($query, -4)) {
            throw new Exception('File type not supported');
        }

        return true;
    }

    /**
     * @param string $query
     *
     * @return ResourceInterface[]
     */
    public function findResources($query)
    {
        $fullQueryPath = realpath($query);

        $fullSpecPath = $this->getFullSpecPath();

        if ('.php' === substr($query, -4)) {
            $resources = array($this->createResourceFromSpecFile($fullQueryPath));
        } else {
            $resources = $this->createResourcesFromSpecFiles($fullSpecPath);
        }

        return $resources;
    }

    /**
     * @param string $classname
     *
     * @return boolean
     */
    public function supportsClass($classname)
    {
        $resource = $this->createResource($classname);
        if (!$this->filesystem->pathExists($resource->getSrcFilename())) {

            return false;
        }

        $isSupported = preg_match('/^(([a-zA-Z0-9]+)_?)+$/', $classname);

        return $isSupported;
    }

    /**
     * @param string $classname
     *
     * @return ResourceInterface|null
     */
    public function createResource($classname)
    {
        $parts = array_map(function($part) {
            return strtolower($part);
        }, preg_split('/_/', $classname));

        return new PSR0Resource($parts, $this, $classname);
    }

    /**
     * @return integer
     */
    public function getPriority()
    {
        return 0;
    }

    /**
     * @return string
     */
    public function getSrcPath()
    {
        return $this->srcPath;
    }

    /**
     * @return string
     */
    public function getSpecPath()
    {
        return $this->specPath;
    }

    /**
     * @return string
     */
    public function getSpecNamespace()
    {
        return $this->specSubNamespace;
    }

    /**
     * @return string
     */
    public function getSpecifiedClass()
    {
        return $this->specifiedClass;
    }

    /**
     * @param $specPath
     *
     * @return null|ResourceInterface
     *
     * @throws \PhpSpec\Exception\Exception
     */
    private function createResourceFromSpecFile($specPath)
    {
        preg_match('/^class\s+([a-zA-Z0-9_]+)Spec/m', $this->filesystem->getFileContents($specPath), $matches);

        if (count($matches) < 2) {
            throw new Exception('Could not create resource from ', $specPath);
        }

        return $this->createResource($matches[1]);
    }

    /**
     * @param $fullSpecPath
     *
     * @return array
     */
    private function createResourcesFromSpecFiles($fullSpecPath)
    {
        $resources = array();

        foreach ($this->filesystem->findSpecFilesIn($fullSpecPath) as $file) {
            $specFile = $file->getRealPath();
            $resources[] = $this->createResourceFromSpecFile($specFile);
        }

        return $resources;
    }

    /**
     * @return string
     */
    private function getFullSpecPath()
    {
        return rtrim(realpath(str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $this->specPath)), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
}
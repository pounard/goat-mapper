<?php

declare(strict_types=1);

namespace Goat\Mapper\Cache\FileLocator;

use Goat\Mapper\Cache\Inflector\DefaultClassNameInflector;

class DefaultFileLocator implements FileLocator
{
    protected string $generatedClassDirectory;

    public function __construct(string $generatedClassDirectory)
    {
        $this->generatedClassDirectory = $generatedClassDirectory;
    }

    /**
     * {@inheritDoc}
     */
    public function getFileName(string $generatedClassName): string
    {
        // @todo make it configurable
        return $this->doGetFileName(DefaultClassNameInflector::DEFAULT_NAMESPACE, $generatedClassName);
    }

    private function doGetFileName(string $namespacePrefix, string $generatedClassName): string
    {
        $namespacePrefix = \trim($namespacePrefix, '\\') . '\\';
        $generatedClassName = \trim($generatedClassName, '\\');

        $length = \strlen($namespacePrefix);
        if ($namespacePrefix === \substr($generatedClassName, 0, $length)) {
            $classNameSuffix = \substr($generatedClassName, $length);
        } else {
            $classNameSuffix = $generatedClassName;
        }

        return $this->generatedClassDirectory . '/' . str_replace('\\', '/', $classNameSuffix) . '.php';
    }
}

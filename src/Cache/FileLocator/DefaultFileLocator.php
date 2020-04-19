<?php

declare(strict_types=1);

namespace Goat\Mapper\Cache\FileLocator;

use Goat\Mapper\Cache\Inflector\DefaultClassNameInflector;

class DefaultFileLocator implements FileLocator
{
    private string $generatedClassDirectory;
    private string $namespacePrefix;

    public function __construct(string $generatedClassDirectory, ?string $namespacePrefix = null)
    {
        $this->generatedClassDirectory = $generatedClassDirectory;
        $this->namespacePrefix = $namespacePrefix ?? DefaultClassNameInflector::DEFAULT_NAMESPACE;
    }

    /**
     * {@inheritDoc}
     */
    public function getFileName(string $generatedClassName): string
    {
        return $this->doGetFileName($this->namespacePrefix, $generatedClassName);
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

<?php

declare(strict_types=1);

namespace Goat\Mapper\Cache\Inflector;

final class DefaultClassNameInflector implements ClassNameInflector
{
    const DEFAULT_NAMESPACE = 'Goat\\Mapper\\Generated\\Definition';

    private string $namespacePrefix;
    private ?string $namespaceInfix = null;

    public function __construct(?string $namespacePrefix = null, ?string $namespaceInfix = 'Generated\\Definition')
    {
        $this->namespacePrefix = $namespacePrefix ?? self::DEFAULT_NAMESPACE;
        $this->namespaceInfix = $namespaceInfix ? \trim($namespaceInfix, '\\') : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getGeneratedClassName(string $className): string
    {
        $namespacePrefix = \trim($this->namespacePrefix, '\\') . '\\';
        $generatedClassName = \trim($className, '\\');

        $length = \strlen($namespacePrefix);
        if ($namespacePrefix === \substr($generatedClassName, 0, $length)) {
            $classNameSuffix = \substr($generatedClassName, $length);
        } else {
            $classNameSuffix = $generatedClassName;
        }

        if ($this->namespaceInfix) {
            return $namespacePrefix . $this->namespaceInfix . '\\' . $classNameSuffix;
        }

        return $namespacePrefix . $classNameSuffix;
    }
}

<?php

declare(strict_types=1);

namespace Goat\Mapper\Cache\Inflector;

final class DefaultClassNameInflector implements ClassNameInflector
{
    const DEFAULT_NAMESPACE = 'Goat\\Mapper\\Generated\\Definition';

    /**
     * {@inheritDoc}
     */
    public function getGeneratedClassName(string $className, array $options = []) : string
    {
        return self::DEFAULT_NAMESPACE . '\\' .$className;
    }

    /**
     * Converts the given parameters into a likely-unique hash
     *
     * @param mixed[] $parameters
     *
    public function hashParameters(array $parameters) : string
    {
        return md5(serialize($parameters));
    }
     */

    /**
     * Converts the given parameters into a set of characters that are safe to
     * use in a class name
     *
     * @param mixed[] $parameters
     *
    public function encodeParameters(array $parameters) : string
    {
        return base64_encode(serialize($parameters));
    }
     */
}

<?php

declare(strict_types=1);

namespace Goat\Mapper\Cache\Inflector;

interface ClassNameInflector
{
    /**
     * Compute generated class name.
     */
    public function getGeneratedClassName(string $className, array $options = []) : string;
}

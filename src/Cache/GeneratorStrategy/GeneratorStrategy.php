<?php

declare(strict_types=1);

namespace Goat\Mapper\Cache\GeneratorStrategy;

interface GeneratorStrategy
{
    /**
     * Generate class.
     */
    public function generate(string $generatedClassName, string $generatedCode): void;
}

<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Registry;

use Goat\Mapper\Definition\Builder\DefinitionBuilder;

/**
 * Self-defined entity provide an quick and dirty way to defined your entity
 * columns, table and relationships.
 */
interface StaticEntityDefinition
{
    /**
     * Define entity using the given builder.
     */
    public static function defineEntity(DefinitionBuilder $builder): void;
}

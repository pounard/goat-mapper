<?php

declare(strict_types=1);

namespace Goat\Mapper\Entity;

use Goat\Mapper\Definition\Builder\DefinitionBuilder;

/**
 * Self-defined entity provide an quick and dirty way to defined your entity
 * columns, table and relationships.
 */
interface SelfDefinedEntity
{
    /**
     * Define entity using the given builder.
     */
    public static function defineEntity(DefinitionBuilder $builder): void;
}

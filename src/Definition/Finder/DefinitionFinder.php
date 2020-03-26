<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Builder;

use Goat\Mapper\Definition\RepositoryDefinition;

/**
 * Create definition for an entity.
 */
interface DefinitionFinder
{
    /**
     * Find definition for an entity.
     */
    public function find(string $className): RepositoryDefinition;
}

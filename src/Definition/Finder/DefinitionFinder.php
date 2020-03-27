<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Builder;

use Goat\Mapper\Definition\RepositoryDefinition;

/**
 * Definition finder is the runtime definition generator.
 */
interface DefinitionFinder
{
    /**
     * Find definition for an entity.
     */
    public function find(string $className): RepositoryDefinition;
}

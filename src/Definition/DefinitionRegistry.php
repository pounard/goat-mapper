<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition;

use Goat\Mapper\Error\InvalidRepositoryDefinitionError;

/**
 * This component is meant to be plug project's entities definition into this
 * API, allowing for repository definitions to be dumped in a performant fashion
 * for very quick repository definition creation.
 *
 * @todo implementation that generates code for hydrating definition (using new class(...)).
 */
interface DefinitionRegistry
{
    /**
     * @throws InvalidRepositoryDefinitionError
     */
    public function getDefinition(string $className): RepositoryDefinition;
}

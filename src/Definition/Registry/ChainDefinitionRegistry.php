<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Registry;

use Goat\Mapper\Definition\RepositoryDefinition;
use Goat\Mapper\Error\RepositoryDoesNotExistError;

final class ChainDefinitionRegistry implements DefinitionRegistry
{
    use DefinitionRegistryTrait;

    /** @var DefinitionRegistry[] */
    private iterable $instances;

    /** @param DefinitionRegistry[] $instances */
    public function __construct(iterable $instances)
    {
        $this->instances = $instances;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition(string $className): RepositoryDefinition
    {
        foreach ($this->instances as $instance) {
            try {
                return $instance->getDefinition($className);
            } catch (RepositoryDoesNotExistError $e) {
                // Silence the RepositoryDoesNotExistError but not others:
                // we must let configuration error passes so the user is
                // aware of problems.
            }
        }

        $this->repositoryDoesNotExist($className);
    }
}

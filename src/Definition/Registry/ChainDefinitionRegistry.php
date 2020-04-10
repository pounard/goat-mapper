<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Registry;

use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Error\EntityDoesNotExistError;

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
    public function getDefinition(string $className): Entity
    {
        foreach ($this->instances as $instance) {
            try {
                return $instance->getDefinition($className);
            } catch (EntityDoesNotExistError $e) {
                // Silence the EntityDoesNotExistError but not others:
                // we must let configuration error passes so the user is
                // aware of problems.
            }
        }

        $this->entityDoesNotExist($className);
    }
}

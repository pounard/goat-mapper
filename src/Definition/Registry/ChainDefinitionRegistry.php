<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Registry;

use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Error\EntityDoesNotExistError;

final class ChainDefinitionRegistry implements DefinitionRegistry
{
    use DefinitionRegistryTrait;

    /** @var DefinitionRegistry[] */
    private array $instances;

    /** @param DefinitionRegistry[] $instances */
    public function __construct(iterable $instances = null)
    {
        if (\is_array($instances)) {
            $this->instances = $instances;
        } else if (null !== $instances) {
            $this->instances = \iterator_to_array($instances);
        } else {
            $this->instances = [];
        }
    }

    /**
     * Add single instance to chain. Note this can work only i
     */
    public function add(DefinitionRegistry $definitionRegistry): void
    {
        $this->instances[] = $definitionRegistry;
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

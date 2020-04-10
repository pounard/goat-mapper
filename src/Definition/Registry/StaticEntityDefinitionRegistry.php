<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Registry;

use Goat\Mapper\Definition\Builder\DefinitionBuilder;
use Goat\Mapper\Definition\Graph\Entity;

final class StaticEntityDefinitionRegistry implements DefinitionRegistry
{
    use DefinitionRegistryTrait;

    private ?DefinitionRegistry $parentDefinitionRegistry;

    public function setParentDefinitionRegistry(DefinitionRegistry $parentDefinitionRegistry = null): void
    {
        $this->parentDefinitionRegistry = $parentDefinitionRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition(string $className): Entity
    {
        if (\in_array(StaticEntityDefinition::class, \class_implements($className))) {
            $builder = new DefinitionBuilder($className);

            \call_user_func([$className, 'defineEntity'], $builder);

            return $builder->compile($this->parentDefinitionRegistry ?? $this);
        }

        $this->entityDoesNotExist($className);
    }
}

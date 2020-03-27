<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Registry;

use Goat\Mapper\Definition\RepositoryDefinition;
use Goat\Mapper\Definition\Builder\DefinitionBuilder;

final class StaticEntityDefinitionRegistry implements DefinitionRegistry
{
    use DefinitionRegistryTrait;

    /**
     * {@inheritdoc}
     */
    public function getDefinition(string $className): RepositoryDefinition
    {
        if (\in_array(StaticEntityDefinition::class, \class_implements($className))) {
            $builder = new DefinitionBuilder($className);

            \call_user_func([$className, 'defineEntity'], $builder);

            return $builder->compile();
        }

        $this->repositoryDoesNotExist($className);
    }
}

<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Registry;

use Goat\Mapper\Definition\RepositoryDefinition;
use Goat\Mapper\Error\RepositoryDoesNotExistError;

final class CacheDefinitionRegistry implements DefinitionRegistry
{
    use DefinitionRegistryTrait;

    private DefinitionRegistry $decorated;

    /** array<string,bool> */
    private array $misses = [];

    /** @var array<string,RepositoryDefinition> */
    private array $hits = [];

    /** @param DefinitionRegistry[] $instances */
    public function __construct(DefinitionRegistry $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition(string $className): RepositoryDefinition
    {
        if ($instance = ($this->hits[$className] ?? null)) {
            return $instance;
        }

        if (isset($this->misses[$className])) {
            $this->repositoryDoesNotExist($className);
        }

        try {
            return $this->hits[$className] = $this->decorated->getDefinition($className);
        } catch (RepositoryDoesNotExistError $e) {
            $this->misses[$className] = true;
        }

        $this->repositoryDoesNotExist($className);
    }
}

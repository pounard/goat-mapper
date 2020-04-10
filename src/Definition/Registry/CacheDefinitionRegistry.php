<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Registry;

use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Error\EntityDoesNotExistError;

final class CacheDefinitionRegistry implements DefinitionRegistry
{
    use DefinitionRegistryTrait;

    private DefinitionRegistry $decorated;
    /** array<string,bool> */
    private array $misses = [];
    /** @var array<string,Entity> */
    private array $hits = [];

    public function __construct(DefinitionRegistry $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition(string $className): Entity
    {
        if ($instance = ($this->hits[$className] ?? null)) {
            return $instance;
        }

        if (isset($this->misses[$className])) {
            $this->repositoryDoesNotExist($className);
        }

        try {
            return $this->hits[$className] = $this->decorated->getDefinition($className);
        } catch (EntityDoesNotExistError $e) {
            $this->misses[$className] = true;
        }

        $this->entityDoesNotExist($className);
    }
}

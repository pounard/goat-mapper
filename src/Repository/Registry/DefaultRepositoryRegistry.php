<?php

declare(strict_types=1);

namespace Goat\Mapper\Repository\Registry;

use Goat\Mapper\Repository\Repository;
use Goat\Mapper\Repository\Factory\RepositoryFactory;

final class DefaultRepositoryRegistry implements RepositoryRegistry
{
    use RepositoryRegistryTrait;

    private RepositoryFactory $factory;
    /** array<string,bool> */
    private array $misses = [];
    /** @var array<string,Repository> */
    private array $hits = [];

    public function __construct(RepositoryFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository(string $className): Repository
    {
        if ($instance = ($this->hits[$className] ?? null)) {
            return $instance;
        }
        if (isset($this->misses[$className])) {
            $this->repositoryDoesNotExist($className);
        }
        if ($repository = $this->factory->createRepository($className)) {
            return $this->hits[$className] = $repository;
        } else {
            $this->misses[$className] = true;
        }

        $this->repositoryDoesNotExist($className);
    }
}

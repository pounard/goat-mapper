<?php

declare(strict_types=1);

namespace Goat\Mapper\Repository\Factory;

use Goat\Mapper\Repository\Repository;

final class ChainRepositoryFactory implements RepositoryFactory
{
    /** @var RepositoryFactory[] */
    private array $instances;

    /** @param RepositoryFactory[] $instances */
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
     * Add single instance to chain.
     */
    public function add(RepositoryFactory $repositoryFactory): void
    {
        $this->instances[] = $repositoryFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function createRepository(string $className): ?Repository
    {
        foreach ($this->instances as $instance) {
            if ($repository = $instance->createRepository($className)) {
                return $repository;
            }
        }

        return null;
    }
}

<?php

declare(strict_types=1);

namespace Goat\Mapper\Repository\Factory;

use Goat\Mapper\EntityManager;
use Goat\Mapper\Error\ConfigurationError;
use Goat\Mapper\Error\IncompleteObjectInitializationError;
use Goat\Mapper\Repository\DefaultRepository;
use Goat\Mapper\Repository\Repository;

final class DefaultRepositoryFactory implements RepositoryFactory
{
    private ?EntityManager $entityManager = null;

    public function setEntityManager(EntityManager $entityManager): void
    {
        if ($this->entityManager) {
            throw new ConfigurationError("You cannot set the entity manager twice");
        }

        $this->entityManager = $entityManager;
    }

    private function getEntityManager(): EntityManager
    {
        if (!$this->entityManager) {
            throw new IncompleteObjectInitializationError("Entity manager was not set.");
        }

        return $this->entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function createRepository(string $className): ?Repository
    {
        return new DefaultRepository($className, $this->getEntityManager());
    }
}

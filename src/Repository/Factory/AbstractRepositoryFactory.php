<?php

declare(strict_types=1);

namespace Goat\Mapper\Repository\Factory;

use Goat\Mapper\EntityManager;
use Goat\Mapper\Error\ConfigurationError;
use Goat\Mapper\Error\IncompleteObjectInitializationError;

abstract class AbstractRepositoryFactory implements RepositoryFactory
{
    private ?EntityManager $entityManager = null;

    public final function setEntityManager(EntityManager $entityManager): void
    {
        if ($this->entityManager) {
            // @codeCoverageIgnoreStart
            throw new ConfigurationError("You cannot set the entity manager twice");
            // @codeCoverageIgnoreEnd
        }

        $this->entityManager = $entityManager;
    }

    protected final function getEntityManager(): EntityManager
    {
        if (!$this->entityManager) {
            // @codeCoverageIgnoreStart
            throw new IncompleteObjectInitializationError("Entity manager was not set.");
            // @codeCoverageIgnoreEnd
        }

        return $this->entityManager;
    }
}

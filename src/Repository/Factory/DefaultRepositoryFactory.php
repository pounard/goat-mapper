<?php

declare(strict_types=1);

namespace Goat\Mapper\Repository\Factory;

use Goat\Mapper\Repository\DefaultRepository;
use Goat\Mapper\Repository\Repository;

final class DefaultRepositoryFactory extends AbstractRepositoryFactory
{
    /**
     * {@inheritdoc}
     */
    public function createRepository(string $className): ?Repository
    {
        return new DefaultRepository($className, $this->getEntityManager());
    }
}

<?php

declare(strict_types=1);

namespace Goat\Mapper\Repository\Factory;

use Goat\Mapper\Repository\Repository;

interface RepositoryFactory
{
    /**
     * Create repository definition.
     */
    public function createRepository(string $className): ?Repository;
}

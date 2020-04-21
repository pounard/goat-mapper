<?php

declare(strict_types=1);

namespace Goat\Mapper\Repository\Registry;

use Goat\Mapper\Repository\Repository;

interface RepositoryRegistry
{
    /**
     * Get repository definition.
     */
    public function getRepository(string $className): Repository;
}

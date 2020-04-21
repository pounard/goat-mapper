<?php

declare(strict_types=1);

namespace Goat\Mapper\Repository\Registry;

use Goat\Mapper\Error\RepositoryDoesNotExistError;

trait RepositoryRegistryTrait
{
    private function repositoryDoesNotExist(string $className): void
    {
        throw new RepositoryDoesNotExistError(\sprintf("There is no known repository for class %s.", $className));
    }
}

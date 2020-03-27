<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Registry;

use Goat\Mapper\Error\RepositoryDoesNotExistError;

trait DefinitionRegistryTrait
{
    private function repositoryDoesNotExist(string $className): void
    {
        throw new RepositoryDoesNotExistError(\sprintf("There is no known registery for class %s.", $className));
    }
}

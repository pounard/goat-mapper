<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Registry;

use Goat\Mapper\Error\EntityDoesNotExistError;

trait DefinitionRegistryTrait
{
    private function entityDoesNotExist(string $className): void
    {
        throw new EntityDoesNotExistError(\sprintf("There is no known registery for class %s.", $className));
    }
}

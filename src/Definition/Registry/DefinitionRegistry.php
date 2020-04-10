<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Registry;

use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Error\EntityDoesNotExistError;

interface DefinitionRegistry
{
    /**
     * @throws EntityDoesNotExistError
     */
    public function getDefinition(string $className): Entity;
}

<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Mock;

use Goat\Mapper\Definition\Builder\DefinitionBuilder;
use Goat\Mapper\Definition\Registry\StaticEntityDefinition;

class Order implements StaticEntityDefinition
{
    public static function defineEntity(DefinitionBuilder $builder): void
    {
        $builder->setTableName('order');
    }
}

<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Builder\Relation;

use Goat\Mapper\Definition\Graph\Relation;
use Goat\Mapper\Definition\Registry\DefinitionRegistry;

interface RelationDefinitionBuilder
{
    public function compile(DefinitionRegistry $definitionRegistry): Relation;
}

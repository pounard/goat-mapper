<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph;

use Goat\Mapper\Definition\Key;

abstract class RelationSimple extends Relation
{
    /**
     * Get key in target table, it might be the primary key.
     */
    abstract public function getTargetKey(): Key;

    /**
     * Get key in source table, it might be the primary key.
     */
    abstract public function getSourceKey(): Key;
}

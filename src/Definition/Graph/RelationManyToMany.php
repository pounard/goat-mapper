<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph;

use Goat\Mapper\Definition\Key;
use Goat\Mapper\Definition\Table;

/**
 * Many to many relations necessitate an extra mapping table.
 *
 * We don't consider that one to many or many to one can be 
 */
interface RelationManyToMany extends Relation
{
    /**
     * Get mapping table.
     */
    public function getMappingTable(): Table;

    /**
     * Get target relation key in mapping table.
     */
    public function getMappingTargetKey(): Key;

    /**
     * Get source relation key in mapping table.
     */
    public function getMappingSourceKey(): Key;
}

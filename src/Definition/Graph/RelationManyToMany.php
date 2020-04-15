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
     * Is source key in mapping table different from source entity primary key?
     */
    public function hasMappingSourceKey(): bool;

    /**
     * Get target relation key in mapping table.
     */
    public function getMappingTargetKey(): Key;

    /**
     * Is target key in mapping table different from target entity primary key?
     */
    public function hasMappingTargetKey(): bool;

    /**
     * Get source relation key in mapping table.
     */
    public function getMappingSourceKey(): Key;
}

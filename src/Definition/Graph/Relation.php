<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph;

use Goat\Mapper\Definition\Key;

interface Relation extends Property
{
    const MODE_ONE_TO_ONE = 1;
    const MODE_ONE_TO_MANY = 2;
    const MODE_MANY_TO_ONE = 3;
    const MODE_MANY_TO_MANY = 4;

    /**
     * Get inverse relation (from entity to owner).
     */
    public function getInverseRelation(): Relation;

    /**
     * Will the other side be a collection?
     */
    public function isMultiple(): bool;

    /**
     * Get relation mode.
     */
    public function getMode(): int;

    /**
     * Get related entity.
     */
    public function getEntity(): Entity;

    /**
     * Get key in target table, it might be the primary key.
     */
    public function getTargetKey(): Key;

    /**
     * Get key in source table, it might be the primary key.
     */
    public function getSourceKey(): Key;
}

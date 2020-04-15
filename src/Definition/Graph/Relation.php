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
     * Get target class name.
     */
    public function getClassName(): string;

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
     * Is source key different from source entity primary key?
     */
    public function hasSourceKey(): bool;

    /**
     * Get key in source table, it might be the primary key.
     */
    public function getSourceKey(): Key;

    /**
     * Is target key different from target entity primary key?
     */
    public function hasTargetKey(): bool;

    /**
     * Get key in target table, it might be the primary key.
     */
    public function getTargetKey(): Key;
}

<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph;

use Goat\Mapper\Definition\Key;

/**
 * Many to many relations necessitate an extra mapping table.
 *
 * We don't consider that one to many or many to one can be 
 */
class RelationManyToMany extends Relation
{
    private Mapping $mapping;
    private ?Key $sourceKey;
    private ?Key $targetKey;

    public function __construct(Entity $entity, string $name, Mapping $mapping, ?Key $sourceKey, ?Key $targetKey)
    {
        parent::__construct($entity, $name, Relation::MODE_MANY_TO_MANY);

        $this->mapping = $mapping;
        $this->sourceKey = $sourceKey;
        $this->targetKey = $targetKey;
    }

    public function getMapping(): Mapping
    {
        return $this->mapping;
    }

    /**
     * Get key in target if different from primary key.
     */
    public function getTargetKey(): Key
    {
        return $this->targetKey ?? $this->getEntity()->getPrimaryKey();
    }

    /**
     * Get key in source if different from primary key.
     */
    public function getSourceKey(): Key
    {
        return $this->sourceKey ?? $this->getOwner()->getPrimaryKey();
    }
}

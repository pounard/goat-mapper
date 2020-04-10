<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph;

use Goat\Mapper\Definition\Key;

/**
 * For one to many relationships, we consider that the foreign key is always
 * in the target table. Many to many are handled differently.
 */
class RelationOneToMany extends RelationSimple
{
    private ?Key $sourceKey;
    private Key $targetKey;

    public function __construct(Entity $entity, string $name, Key $targetKey, ?Key $sourceKey)
    {
        parent::__construct($entity, $name, Relation::MODE_ONE_TO_MANY);

        $this->sourceKey = $sourceKey;
        $this->targetKey = $targetKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceKey(): Key
    {
        return $this->sourceKey ?? $this->getOwner()->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetKey(): Key
    {
        return $this->targetKey;
    }
}

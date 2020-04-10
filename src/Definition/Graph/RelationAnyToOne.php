<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph;

use Goat\Mapper\Definition\Key;

/**
 * For any to one relationships, we consider that the foreign key is always
 * in the source table.
 */
class RelationAnyToOne extends RelationSimple
{
    private Key $sourceKey;
    private ?Key $targetKey;

    public function __construct(Entity $entity, string $name, Key $sourceKey, ?Key $targetKey, ?int $mode = Relation::MODE_MANY_TO_ONE)
    {
        if (Relation::MODE_MANY_TO_ONE !== $mode && Relation::MODE_ONE_TO_ONE !== $mode) {
            throw new \InvalidArgumentException(\sprintf("Mode must be many to one or one to one.", __CLASS__));
        }

        parent::__construct($entity, $name, $mode);

        $this->sourceKey = $sourceKey;
        $this->targetKey = $targetKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceKey(): Key
    {
        return $this->sourceKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetKey(): Key
    {
        return $this->targetKey ?? $this->getEntity()->getPrimaryKey();
    }
}

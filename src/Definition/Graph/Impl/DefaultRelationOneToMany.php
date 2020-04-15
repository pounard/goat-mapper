<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph\Impl;

use Goat\Mapper\Definition\Key;
use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Definition\Graph\Relation;
use Goat\Mapper\Definition\Graph\RelationOneToMany;

final class DefaultRelationOneToMany extends AbstractRelation implements RelationOneToMany
{
    public function __construct(Entity $entity, string $name, string $className)
    {
        parent::__construct($entity, $name, $className, Relation::MODE_ONE_TO_MANY);
    }

    /**
     * {@inheritdoc}
     */
    protected function findDefaultSourceKey(): Key
    {
        return $this->getOwner()->getPrimaryKey();
    }
}

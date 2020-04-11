<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph\Impl;

use Goat\Mapper\Definition\Key;
use Goat\Mapper\Definition\Table;
use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Definition\Graph\Relation;
use Goat\Mapper\Definition\Graph\RelationManyToMany;
use Goat\Mapper\Error\ConfigurationError;

final class DefaultRelationManyToMany extends AbstractRelation implements RelationManyToMany
{
    private ?Table $mappingTable = null;
    private ?Key $mappingSourceKey = null;
    private ?Key $mappingTargetKey = null;

    public function __construct(Entity $entity, string $name)
    {
        parent::__construct($entity, $name, Relation::MODE_MANY_TO_MANY);
    }

    /**
     * Set mapping table
     */
    public function setMappingTable(Table $mappingTable): void
    {
        $this->mappingTable = $mappingTable;
    }

    /**
     * {@inheritdoc}
     */
    public function getMappingTable(): Table
    {
        return $this->mappingTable ?? $this->mappingTableIsNotSet();
    }

    /**
     * Set source key in mapping table.
     */
    public function setMappingSourceKey(Key $mappingSourceKey): void
    {
        $this->mappingSourceKey = $mappingSourceKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getMappingSourceKey(): Key
    {
        return $this->sourceKey ?? $this->getOwner()->getPrimaryKey();
    }

    /**
     * Set target key in mapping table.
     */
    public function setMappingTargetKey(Key $mappingSourceKey): void
    {
        $this->mappingSourceKey = $mappingSourceKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getMappingTargetKey(): Key
    {
        return $this->targetKey ?? $this->getEntity()->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    protected function findDefaultSourceKey(): Key
    {
        return $this->getOwner()->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    protected function findDefaultTargetKey(): Key
    {
        return $this->getEntity()->getPrimaryKey();
    }

    /**
     * Entity was not set.
     */
    private function mappingTableIsNotSet(): void
    {
        throw new ConfigurationError("Mapping table is missing from definition.");
    }
}

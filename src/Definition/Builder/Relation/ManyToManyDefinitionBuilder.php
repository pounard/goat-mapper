<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Builder\Relation;

use Goat\Mapper\Definition\Key;
use Goat\Mapper\Definition\Table;
use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Definition\Graph\Relation;
use Goat\Mapper\Definition\Graph\Impl\DefaultRelationManyToMany;
use Goat\Driver\ConfigurationError;

final class ManyToManyDefinitionBuilder extends AbstractRelationDefinitionBuilder
{
    private ?Table $mappingTable;
    private ?Key $mappingSourceKey;
    private ?Key $mappingTargetKey;

    public function __construct(string $sourcePropertyName, string $targetClassName)
    {
        parent::__construct($sourcePropertyName, $targetClassName, Relation::MODE_MANY_TO_MANY);
    }

    /**
     * Set mapping table.
     */
    public function setMappingTable(string $tableName, ?string $schema = null): void
    {
        $this->mappingTable = new Table($tableName, $schema);
    }

    /**
     * Set source key in mapping table.
     *
     * @param array<string,string> $propertyTypeMap
     *   Keys are property names, values are property SQL types. Properties
     *   must have been validated, whereas types will not: types must be types
     *   that goat-query understand and will be propagated as-is to there.
     */
    public function setMappingSourceKey(array $propertyTypeMap): void
    {
        $this->mappingSourceKey = $this->doCompileKey($propertyTypeMap);
    }

    /**
     * Set target key in mapping table.
     *
     * @param array<string,string> $propertyTypeMap
     *   Keys are property names, values are property SQL types. Properties
     *   must have been validated, whereas types will not: types must be types
     *   that goat-query understand and will be propagated as-is to there.
     */
    public function setMappingTargetKey(array $propertyTypeMap): void
    {
        $this->mappingTargetKey = $this->doCompileKey($propertyTypeMap);
    }

    /**
     * Set source key in source table if different from primary key.
     *
     * @param array<string,string> $propertyTypeMap
     *   Keys are property names, values are property SQL types. Properties
     *   must have been validated, whereas types will not: types must be types
     *   that goat-query understand and will be propagated as-is to there.
     */
    public function setSourceKeyIfNotPrimaryKey(array $propertyTypeMap): void
    {
        $this->doSetSourceKey($propertyTypeMap);
    }

    /**
     * Set target key in target table if different from primary key.
     *
     * @param array<string,string> $propertyTypeMap
     *   Keys are property names, values are property SQL types. Properties
     *   must have been validated, whereas types will not: types must be types
     *   that goat-query understand and will be propagated as-is to there.
     */
    public function setTargetKeyIfNotPrimaryKey(array $propertyTypeMap): void
    {
        $this->doSetTargetKey($propertyTypeMap);
    }

    private function compileMappingTable(): Table
    {
        if (!$this->mappingTable) {
            throw new ConfigurationError(\sprintf(
                "Relation for property '%s' mapping table name must be specified using %s::setMappingTable().",
                $this->sourcePropertyName,
                __CLASS__
            ));
        }

        return $this->mappingTable;
    }

    private function compileMappingSourceKey(): Key
    {
        if (null === $this->mappingSourceKey) {
            throw new ConfigurationError(\sprintf(
                "Relation for property '%s' mapping source key (source key in mapping table) must be specified using %s::setMappingSourceKey().",
                $this->sourcePropertyName,
                __CLASS__
            ));
        }

        return $this->mappingSourceKey;
    }

    private function compileMappingTargetKey(): Key
    {
        if (null === $this->mappingTargetKey) {
            throw new ConfigurationError(\sprintf(
                "Relation for property '%s' mapping target key (target key in mapping table) must be specified using %s::setMappingTargetKey().",
                $this->sourcePropertyName,
                __CLASS__
            ));
        }

        return $this->mappingTargetKey;
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompile(Entity $entity): Relation
    {
        $relation = new DefaultRelationManyToMany($entity, $this->sourcePropertyName);
        $relation->setMappingTable($this->compileMappingTable());

        $mappingSourceKey = $this->compileMappingSourceKey();
        $mappingTargetKey = $this->compileMappingTargetKey();

        if ($key = $this->compileSourceKey()) {
            $this->ensureKeysCompatibility(
                $key,
                $mappingSourceKey,
                "Source key in source table must be compatible with source key in mapping table."
            );

            $relation->setSourceKey($key);
        }

        if ($key = $this->compileTargetKey()) {
            $this->ensureKeysCompatibility(
                $key,
                $mappingTargetKey,
                "Target key in target table must be compatible with target key in mapping table."
            );

            $relation->setTargetKey($key);
        }

        $relation->setMappingSourceKey($mappingSourceKey);
        $relation->setMappingTargetKey($mappingTargetKey);

        return $relation;
    }
}

<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Builder\Relation;

use Goat\Mapper\Definition\Key;
use Goat\Mapper\Definition\Table;
use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Definition\Graph\Relation;
use Goat\Mapper\Definition\Graph\Impl\DefaultRelationManyToMany;

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
     * Set target key
     *
     * @param array<string,string> $propertyTypeMap
     *   Keys are property names, values are property SQL types. Properties
     *   must have been validated, whereas types will not: types must be types
     *   that goat-query understand and will be propagated as-is to there.
     */
    public function setTargetKeyIfNotPrimaryKey(array $propertyTypeMap): void
    {
        $this->doSetSourceKey($propertyTypeMap);
    }

    /**
     * Set source key if different from primary key
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
     * {@inheritdoc}
     */
    protected function doCompile(Entity $entity): Relation
    {
        throw new \Exception("Not implemented yet.");

        $relation = new DefaultRelationManyToMany($entity, $this->sourcePropertyName);

        if ($key = $this->compileSourceKey()) {
            $relation->setSourceKey($key);
        }
        if ($key = $this->compileTargetKey()) {
            $relation->setTargetKey($key);
        }

        return $relation;
    }
}

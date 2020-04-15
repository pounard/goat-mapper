<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Builder\Relation;

use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Definition\Graph\Relation;
use Goat\Mapper\Definition\Graph\Impl\DefaultRelationOneToMany;

/**
 * Build definition for an entity.
 */
final class OneToManyDefinitionBuilder extends AbstractRelationDefinitionBuilder
{
    public function __construct(string $sourcePropertyName, string $targetClassName)
    {
        parent::__construct($sourcePropertyName, $targetClassName, Relation::MODE_ONE_TO_MANY);
    }

    /**
     * Set target key if different from primary key
     *
     * @param array<string,string> $propertyTypeMap
     *   Keys are property names, values are property SQL types. Properties
     *   must have been validated, whereas types will not: types must be types
     *   that goat-query understand and will be propagated as-is to there.
     */
    public function setTargetKey(array $propertyTypeMap): void
    {
        $this->doSetTargetKey($propertyTypeMap);
    }

    /**
     * Set source key
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
        $relation = new DefaultRelationOneToMany(
            $entity,
            $this->sourcePropertyName,
            $this->targetClassName
        );

        if ($key = $this->compileSourceKey()) {
            $relation->setSourceKey($key);
        }
        if ($key = $this->compileTargetKey(true)) {
            $relation->setTargetKey($key);
        }

        return $relation;
    }
}

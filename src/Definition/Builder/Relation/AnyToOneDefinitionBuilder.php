<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Builder\Relation;

use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Definition\Graph\Relation;
use Goat\Mapper\Definition\Graph\Impl\DefaultRelationAnyToOne;

final class AnyToOneDefinitionBuilder extends AbstractRelationDefinitionBuilder
{
    /**
     * Set source key
     *
     * @param array<string,string> $propertyTypeMap
     *   Keys are property names, values are property SQL types. Properties
     *   must have been validated, whereas types will not: types must be types
     *   that goat-query understand and will be propagated as-is to there.
     */
    public function setSourceKey(array $propertyTypeMap): void
    {
        $this->doSetSourceKey($propertyTypeMap);
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
        $this->doSetTargetKey($propertyTypeMap);
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompile(Entity $entity): Relation
    {
        $relation = new DefaultRelationAnyToOne(
            $entity,
            $this->sourcePropertyName,
            $this->targetClassName
        );

        if ($key = $this->compileSourceKey(true)) {
            $relation->setSourceKey($key);
        }
        if ($key = $this->compileTargetKey()) {
            $relation->setTargetKey($key);
        }

        return $relation;
    }
}

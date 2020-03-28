<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Graph\Visitor;

use Goat\Mapper\Query\Entity\QueryHelper;
use Goat\Mapper\Query\Graph\EntityQuery;
use Goat\Mapper\Query\Graph\Node;
use Goat\Mapper\Query\Graph\PropertyNode;

class EagerJoinVisitor extends AbstractVisitor
{
    /**
     * {@inheritdoc}
     */
    public function onPropertyNode(PropertyNode $node, Node $parent, EntityQuery $context): void
    {
        $relation = $context
            ->getDefinitionRegistry()
            ->getDefinition($parent->getClassName())
            ->getRelation($node->getPropertyName())
        ;

        $query = $context->getQuery();
        $targetTableAlias = $node->getAlias();

        // We will always use a LEFT JOIN to avoid ghosting existing source
        // relation objects from missing target entity. Even when the relation
        // is required, we cannot let broken making our entity invisible to our
        // users.
        if ($node->shouldMatch()) {
            QueryHelper::addJoinStatement($query, $relation, $parent->getAlias(), $targetTableAlias);
        } else {
            QueryHelper::addJoinStatement($query, $relation, $parent->getAlias(), $targetTableAlias, true);
        }
    }
}

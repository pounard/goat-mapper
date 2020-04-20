<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Graph\Visitor;

use Goat\Mapper\Definition\Graph\Relation;
use Goat\Mapper\Definition\Graph\RelationAnyToOne;
use Goat\Mapper\Definition\Graph\RelationManyToMany;
use Goat\Mapper\Definition\Graph\RelationOneToMany;
use Goat\Mapper\Error\QueryError;
use Goat\Mapper\Query\Entity\EntityQuery;
use Goat\Mapper\Query\Entity\QueryHelper;
use Goat\Mapper\Query\Graph\Node;
use Goat\Mapper\Query\Graph\PropertyNode;
use Goat\Query\ExpressionRelation;

class EagerJoinVisitor extends AbstractVisitor
{
    /**
     * {@inheritdoc}
     */
    public function onPropertyNode(PropertyNode $node, Node $parent, EntityQuery $context): void
    {
        if ($node->isLazy()) {
            return;
        }

        $relation = $context
            ->getDefinitionRegistry()
            ->getDefinition($parent->getClassName())
            ->getRelation($node->getPropertyName())
        ;

        if ($relation->isMultiple()) {
            // We cannot proceed with eager JOIN with to many relations.
            return;
        }

        if ($relation instanceof RelationAnyToOne) {
            $this->handleAnyToOne($node, $parent, $context, $relation);
        } else if ($relation instanceof RelationOneToMany) {
            throw new QueryError("You cannot eager-join any to many relationships.");
        } else if ($relation instanceof RelationManyToMany) {
            throw new QueryError("You cannot eager-join any to many relationships.");
        } else {
            throw new QueryError(\sprintf("Handling of %d relations is not implemented yet.", \get_class($relation)));
        }
    }

    /**
     * Key is in the relation target table, or in the source table.
     *
     * "a" is the source, "b" the target.
     *
     * We will always use a LEFT JOIN to avoid ghosting existing source
     * relation objects from missing target entity. Even when the relation
     * is required, we cannot let broken making our entity invisible to our
     * users.
     *
     * SELECT a.*, b.*
     * FROM a
     * LEFT JOIN a ON a.target_id = b.id
     * WHERE a.id = ?
     *
     * Remember that keys might not be the primary key.
     */
    private function handleAnyToOne(
        PropertyNode $node,
        Node $parent,
        EntityQuery $context,
        Relation $relation
    ): void {
        $query = $context->getQuery();

        $sourceTableAlias = $parent->getAlias();

        $targetTable = $relation->getEntity()->getTable();
        $targetTableAlias = $node->getAlias();
        $tableExpression = ExpressionRelation::create($targetTable->getName(), $targetTableAlias, $targetTable->getSchema());

        $joinConditions = QueryHelper::createJoinConditions(
            $sourceTableAlias,
            $relation->getSourceKey(),
            $targetTableAlias,
            $relation->getTargetKey()
        );

        if ($node->shouldMatch()) {
            $query->innerJoin($tableExpression, $joinConditions);
        } else {
            $query->leftJoin($tableExpression, $joinConditions);
        }
    }
}

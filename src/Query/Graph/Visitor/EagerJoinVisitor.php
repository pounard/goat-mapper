<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Graph\Visitor;

use Goat\Mapper\Definition\Graph\Relation;
use Goat\Mapper\Definition\Graph\RelationAnyToOne;
use Goat\Mapper\Definition\Graph\RelationManyToMany;
use Goat\Mapper\Definition\Graph\RelationOneToMany;
use Goat\Mapper\Error\QueryError;
use Goat\Mapper\Query\Entity\EntityQuery;
use Goat\Mapper\Query\Graph\Node;
use Goat\Mapper\Query\Graph\PropertyNode;
use Goat\Query\ExpressionColumn;
use Goat\Query\ExpressionRelation;
use Goat\Query\Where;

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

        // We will always use a LEFT JOIN to avoid ghosting existing source
        // relation objects from missing target entity. Even when the relation
        // is required, we cannot let broken making our entity invisible to our
        // users.
        if ($relation instanceof RelationAnyToOne || $relation instanceof RelationOneToMany) {
            $this->handleJoin($node, $parent, $context, $relation);
        } else if ($relation instanceof RelationManyToMany) {
            throw new QueryError("You cannot eager-join a many to many relationship.");
        } else {
            throw new QueryError(\sprintf("Handling of %d relations is not implemented yet.", \get_class($relation)));
        }
    }

    /**
     * Key is in the relation target table, or in the source table.
     *
     * "a" is the source, "b" the target.
     *
     * SELECT a.*, b.*
     * FROM a
     * [INNER|LEFT] JOIN a ON a.id = b.target_id
     * WHERE a.id = ?
     *
     * OR
     *
     * SELECT a.*, b.*
     * FROM a
     * [INNER|LEFT] JOIN a ON a.target_id = b.id
     * WHERE a.id = ?
     *
     * Remember that keys might not be the primary key.
     */
    private function handleJoin(
        PropertyNode $node,
        Node $parent,
        EntityQuery $context,
        Relation $relation
    ): void {
        $query = $context->getQuery();

        $sourceTableAlias = $parent->getAlias();
        $targetTableAlias = $node->getAlias();

        $targetTable = $relation->getEntity()->getTable();
        $tableExpression = ExpressionRelation::create($targetTable->getName(), $targetTableAlias, $targetTable->getSchema());

        $targetKeyColumnsMap = $relation->getTargetKey()->getColumnNames();
        $sourceKeyColumnsMap = $relation->getSourceKey()->getColumnNames();

        $joinConditions = (new Where());
        foreach ($targetKeyColumnsMap as $i => $columnName) {
            $joinConditions->isEqual(
                ExpressionColumn::create($columnName, $targetTableAlias),
                ExpressionColumn::create($sourceKeyColumnsMap[$i], $sourceTableAlias)
            );
        }

        if ($node->shouldMatch()) {
            $query->innerJoin($tableExpression, $joinConditions);
        } else {
            $query->leftJoin($tableExpression, $joinConditions);
        }
    }
}

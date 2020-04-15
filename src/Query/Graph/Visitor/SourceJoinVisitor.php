<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Graph\Visitor;

use Goat\Mapper\Definition\Graph\RelationAnyToOne;
use Goat\Mapper\Definition\Graph\RelationManyToMany;
use Goat\Mapper\Definition\Graph\RelationOneToMany;
use Goat\Mapper\Error\QueryError;
use Goat\Mapper\Query\Entity\EntityQuery;
use Goat\Mapper\Query\Entity\QueryHelper;
use Goat\Mapper\Query\Graph\RootNode;
use Goat\Mapper\Query\Graph\Source;
use Goat\Query\ExpressionColumn;
use Goat\Query\ExpressionRelation;
use Goat\Query\Where;

class SourceJoinVisitor implements RootVisitor
{
    /**
     * {@inheritdoc}
     */
    public function onRootNode(RootNode $node, EntityQuery $context): void
    {
        if ($source = $node->getSource()) {
            $relation = $context
                ->getDefinitionRegistry()
                ->getDefinition($source->getClassName())
                ->getRelation($source->getPropertyName())
            ;

            if ($relation instanceof RelationAnyToOne) {
                $this->handleAnyToOne($source, $node, $context, $relation);
            } else if ($relation instanceof RelationOneToMany) {
                $this->handleOneToMany($source, $node, $context, $relation);
            } else if ($relation instanceof RelationManyToMany) {
                $this->handleManyToMany($source, $node, $context, $relation);
            } else {
                throw new QueryError(\sprintf("Handling of %d relations is not implemented yet.", \get_class($relation)));
            }
        }
    }

    /**
     * Key is in the relation target table.
     *
     * "a" is the source, "b" the target.
     *
     * SELECT b.*
     * FROM b
     * WHERE b.target_id = ?
     *
     * OR, if source key is not its primary key:
     *
     * SELECT b.*
     * FROM b
     * INNER JOIN a ON a.join_key = b.target_id
     * WHERE a.id = ?
     */
    private function handleOneToMany(
        Source $source,
        RootNode $node,
        EntityQuery $context,
        RelationOneToMany $relation
    ): void {
        $query = $context->getQuery();

        $sourcePrimaryKey = $relation->getOwner()->getPrimaryKey();
        $sourceKey = $relation->getSourceKey();

        $targetTableAlias = $node->getAlias();

        if ($sourceKey->equals($sourcePrimaryKey)) {
            // We apply value conditions directly on the target table using
            // the target key, and be happy with it.
            $context->getQuery()->condition(
                QueryHelper::createKeyCondition(
                    $targetTableAlias,
                    $relation->getTargetKey(),
                    $source->getIdentifiers()
                ),
            );
        } else {
            // We create the extra JOIN and apply conditions on the source
            // table primary key.
            $sourceTable = $relation->getOwner()->getTable();
            $sourceTableAlias = $context->getNextAlias($sourceTable->getName());
            $sourceTableExpression = ExpressionRelation::create($sourceTable->getName(), $sourceTableAlias, $sourceTable->getSchema());

            $targetKeyColumnsMap = $relation->getTargetKey()->getColumnNames();
            $sourceKeyColumnsMap = $relation->getSourceKey()->getColumnNames();

            $joinConditions = (new Where());
            foreach ($targetKeyColumnsMap as $i => $columnName) {
                $joinConditions->isEqual(
                    ExpressionColumn::create($columnName, $targetTableAlias),
                    ExpressionColumn::create($sourceKeyColumnsMap[$i], $sourceTableAlias)
                );
            }
            $query->innerJoin($sourceTableExpression, $joinConditions);

            $context->getQuery()->condition(
                QueryHelper::createKeyCondition(
                    $sourceTableAlias,
                    $sourcePrimaryKey,
                    $source->getIdentifiers()
                ),
            );
        }
    }

    /**
     * Key is in the relation source table.
     *
     * "a" is the source, "b" the target.
     *
     * SELECT b.*
     * FROM b
     * INNER JOIN a ON a.target_id = b.id
     * WHERE a.id = ?
     *
     * If relation key is not the primary key, the generated query will remain
     * valid in all cases, there is only one case to handle here.
     */
    private function handleAnyToOne(
        Source $source,
        RootNode $node,
        EntityQuery $context,
        RelationAnyToOne $relation
    ): void {
        $query = $context->getQuery();

        $sourcePrimaryKey = $relation->getOwner()->getPrimaryKey();

        $targetTableAlias = $node->getAlias();

        $sourceTable = $relation->getOwner()->getTable();
        $sourceTableAlias = $context->getNextAlias($sourceTable->getName());
        $sourceTableExpression = ExpressionRelation::create($sourceTable->getName(), $sourceTableAlias, $sourceTable->getSchema());

        $targetKeyColumnsMap = $relation->getTargetKey()->getColumnNames();
        $sourceKeyColumnsMap = $relation->getSourceKey()->getColumnNames();

        $joinConditions = (new Where());
        foreach ($targetKeyColumnsMap as $i => $columnName) {
            $joinConditions->isEqual(
                ExpressionColumn::create($columnName, $targetTableAlias),
                ExpressionColumn::create($sourceKeyColumnsMap[$i], $sourceTableAlias)
            );
        }
        $query->innerJoin($sourceTableExpression, $joinConditions);

        $context->getQuery()->condition(
            QueryHelper::createKeyCondition(
                $sourceTableAlias,
                $sourcePrimaryKey,
                $source->getIdentifiers()
            ),
        );
    }

    /**
     * Key is in a mapping table.
     *
     * "a" is the source, "b" the target.
     *
     * SELECT b.*
     * FROM b
     * INNER JOIN mapping ON mapping.b_id = b.id
     * WHERE mapping.a_id = ?
     *
     * OR, if source key is not its primary key:
     *
     * SELECT b.*
     * FROM b
     * INNER JOIN mapping m ON m.b_id = b.id
     * INNER JOIN a ON a.join_key = m.a_id
     * WHERE mapping.a_id = ?
     */
    private function handleManyToMany(
        Source $source,
        RootNode $node,
        EntityQuery $context,
        RelationManyToMany $relation
    ): void {
        $query = $context->getQuery();

        $sourcePrimaryKey = $relation->getOwner()->getPrimaryKey();
        $sourceKey = $relation->getSourceKey();

        $targetTableAlias = $node->getAlias();

        // Add the mapping table JOIN (which is mandatory).
        $mappingTable = $relation->getMappingTable();
        $mappingTableAlias = $context->getNextAlias($mappingTable->getName());
        $mappingTableExpression = ExpressionRelation::create($mappingTable->getName(), $mappingTableAlias, $mappingTable->getSchema());

        $targetKeyColumnsMap = $relation->getTargetKey()->getColumnNames();
        $targetMappingKeyColumnsMap = $relation->getMappingTargetKey()->getColumnNames();

        $mappingJoinConditions = (new Where());
        foreach ($targetKeyColumnsMap as $i => $columnName) {
            $mappingJoinConditions->isEqual(
                ExpressionColumn::create($columnName, $targetTableAlias),
                ExpressionColumn::create($targetMappingKeyColumnsMap[$i], $mappingTableAlias)
            );
        }
        $query->innerJoin($mappingTableExpression, $mappingJoinConditions);

        if ($sourceKey->equals($sourcePrimaryKey)) {
            // We apply value conditions directly on the mapping table using
            // the source key, and be happy with it.
            $context->getQuery()->condition(
                QueryHelper::createKeyCondition(
                    $mappingTableAlias,
                    $relation->getMappingSourceKey(),
                    $source->getIdentifiers()
                ),
            );
        } else {
            // We create the extra JOIN and apply conditions on the source
            // table primary key.
            $sourceTable = $relation->getOwner()->getTable();
            $sourceTableAlias = $context->getNextAlias($sourceTable->getName());
            $sourceTableExpression = ExpressionRelation::create($sourceTable->getName(), $sourceTableAlias, $sourceTable->getSchema());

            $sourceKeyColumnsMap = $relation->getSourceKey()->getColumnNames();
            $sourceMappingKeyColumnsMap = $relation->getMappingSourceKey()->getColumnNames();

            $sourceJoinConditions = (new Where());
            foreach ($sourceMappingKeyColumnsMap as $i => $columnName) {
                $sourceJoinConditions->isEqual(
                    ExpressionColumn::create($columnName, $mappingTableAlias),
                    ExpressionColumn::create($sourceKeyColumnsMap[$i], $sourceTableAlias)
                );
            }
            $query->innerJoin($sourceTableExpression, $sourceJoinConditions);

            $context->getQuery()->condition(
                QueryHelper::createKeyCondition(
                    $sourceTableAlias,
                    $sourcePrimaryKey,
                    $source->getIdentifiers()
                ),
            );
        }
    }
}

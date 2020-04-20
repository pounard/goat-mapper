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
use Goat\Query\ExpressionRelation;
use Goat\Query\SelectQuery;

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

            // @todo
            //   actually this should be dropped in flavor of LazyJoinVisitor
            //   and MatchVisitor altogether? but we can't until we don't have
            //   inverse relations represented correctly.

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
     * In this case, since this is a one to many relation, we will always
     * be OK with a simple JOIN, we won't risk having duplicates, at least
     * not if you use a proper SQL server and don't have messed-up your
     * entity schema definition.
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

            $query->innerJoin(
                $sourceTableExpression,
                QueryHelper::createJoinConditions(
                    $sourceTableAlias,
                    $sourceKey,
                    $targetTableAlias,
                    $relation->getTargetKey()
                )
            );

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
     * Relation can be many to one, case in which cardinality could create
     * duplicates for the "b" table SELECT. There is many ways to solve this:
     *
     *  - most common one people would think about is by using DISTINCT or
     *    GROUP BY, but this may cause serious query consistency and debug
     *    hell in case we add eager load or condition matching JOIN,
     *
     *  - another way would be to write a WHERE EXISTS (SELECT true FROM a)
     *    which is SQL standard that every backend will understand,
     *
     *  - third one, and maybe more readable is using a CTE instead of the
     *    subquery, but using a JOIN on the CTE and a WHERE EXISTS altogether.
     *
     * Both former options are equivalent in term of semantics, and most SQL
     * backends will optimize the same way. I'd rather use the CTE one for
     * SQL readability (and debug later). We will exclude MySQL < 8 doing
     * this, but anyway people should upgrade, or avoid using MySQL.
     *
     * About performances, in theory, WHERE EXISTS (SELECT ...) and CTE
     * options should be equivalent, because semantically they are equivalent
     * but the real result will depend upon each RDBMS implementation and their
     * query analyzer and optimizer choices. We cannot decentely predict this,
     * so our only option will be to allow the API user to provide hints for
     * writing the query.
     *
     * @todo Implement both solutions and let the user choose.
     *
     * All of this will be true as well for mapping tables JOIN.
     *
     * Version with EXISTS:
     *
     * SELECT b.*
     * FROM b
     * WHERE EXISTS (
     *      SELECT true
     *      FROM a
     *      WHERE
     *          a.target_id = b.id
     *          AND a.id = ?
     * )
     *
     * This also mean that we may re-use CTE for other usages, but let's not
     * try to over-engeneer here, and keep it simple for now. So, EXISTS it
     * will be!
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
        $sourceKey = $relation->getSourceKey();
        $sourcePrimaryKey = $relation->getOwner()->getPrimaryKey();
        $targetTableAlias = $node->getAlias();

        $sourceTable = $relation->getOwner()->getTable();
        $sourceTableAlias = $context->getNextAlias($sourceTable->getName());
        $sourceTableExpression = ExpressionRelation::create($sourceTable->getName(), $sourceTableAlias, $sourceTable->getSchema());

        $exists = (new SelectQuery($sourceTableExpression))
            ->columnExpression('1')
            ->condition(
                QueryHelper::createKeyCondition(
                    $sourceTableAlias,
                    $sourcePrimaryKey,
                    $source->getIdentifiers()
                ),
            )
            ->condition(
                QueryHelper::createJoinConditions(
                    $sourceTableAlias,
                    $sourceKey,
                    $targetTableAlias,
                    $relation->getTargetKey()
                )
            )
        ;

        $context->getQuery()->getWhere()->exists($exists);
    }

    /**
     * Key is in a mapping table.
     *
     * See comment in self::handleAnyToOne() which explains the solutions for
     * avoiding duplicates in SELECT.
     *
     * "a" is the source, "b" the target.
     *
     * SELECT b.*
     * FROM b
     * WHERE EXISTS (
     *     SELECT 1
     *     FROM mapping
     *     WHERE
     *         mapping.b_id = b.id
     *         AND mapping.a_id = ?
     * )
     *
     * OR, if source key is not its primary key:
     *
     * SELECT b.*
     * FROM b
     * WHERE EXISTS (
     *     SELECT 1
     *     FROM mapping m
     *     INNER JOIN a
     *         ON a.join_key = m.a_id
     *     WHERE
     *         m.b_id = b.id
     *         AND mapping.a_id = ?
     * )
     */
    private function handleManyToMany(
        Source $source,
        RootNode $node,
        EntityQuery $context,
        RelationManyToMany $relation
    ): void {
        $sourcePrimaryKey = $relation->getOwner()->getPrimaryKey();
        $sourceKey = $relation->getSourceKey();

        $targetTableAlias = $node->getAlias();

        // Add the mapping table JOIN (which is mandatory).
        $mappingTable = $relation->getMappingTable();
        $mappingTableAlias = $context->getNextAlias($mappingTable->getName());
        $mappingTableExpression = ExpressionRelation::create($mappingTable->getName(), $mappingTableAlias, $mappingTable->getSchema());

        $exists = (new SelectQuery($mappingTableExpression))
            ->columnExpression('1')
            ->condition(
                QueryHelper::createJoinConditions(
                    $targetTableAlias,
                    $relation->getTargetKey(),
                    $mappingTableAlias,
                    $relation->getMappingTargetKey()
                )
            )
        ;

        if ($sourceKey->equals($sourcePrimaryKey)) {
            // We apply value conditions directly on the mapping table using
            // the source key, and be happy with it.
            $exists->condition(
                QueryHelper::createKeyCondition(
                    $mappingTableAlias,
                    $relation->getMappingSourceKey(),
                    $source->getIdentifiers()
                )
            );
        } else {
            // We create the extra JOIN and apply conditions on the source
            // table primary key.
            $sourceTable = $relation->getOwner()->getTable();
            $sourceTableAlias = $context->getNextAlias($sourceTable->getName());
            $sourceTableExpression = ExpressionRelation::create($sourceTable->getName(), $sourceTableAlias, $sourceTable->getSchema());

            $exists->innerJoin(
                $sourceTableExpression,
                QueryHelper::createJoinConditions(
                    $sourceTableAlias,
                    $relation->getSourceKey(),
                    $mappingTableAlias,
                    $relation->getMappingSourceKey()
                )
            );
        }

        $context->getQuery()->getWhere()->exists($exists);
    }
}

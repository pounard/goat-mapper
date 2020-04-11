<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Graph\Visitor;

use Goat\Mapper\Definition\Graph\RelationAnyToOne;
use Goat\Mapper\Definition\Graph\RelationManyToMany;
use Goat\Mapper\Definition\Graph\RelationOneToMany;
use Goat\Mapper\Query\Entity\EntityQuery;
use Goat\Mapper\Query\Entity\QueryHelper;
use Goat\Mapper\Query\Graph\RootNode;

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
                $this->handleKeyInSourceTableRelation(
                    $context,
                    $node->getAlias(),
                    $relation,
                    $source->getIdentifiers(),
                );
            } else if ($relation instanceof RelationOneToMany) {
                $this->handleKeyInTargetTableRelation(
                    $context,
                    $node->getAlias(),
                    $relation,
                    $source->getIdentifiers(),
                );
            } else {
                $this->handleKeyInMappingTableRelation(
                    $context,
                    $node->getAlias(),
                    $relation,
                    $source->getIdentifiers(),
                );
            }
        }
    }

    /**
     * Key is in the relation target table.
     *
     * "a" is the source, "b" the target.
     *
     * a.id -> b.target_id
     *
     * SELECT b.*
     * FROM b
     * WHERE b.target_id = ?
     */
    private function handleKeyInTargetTableRelation(
        EntityQuery $context,
        string $targetTableAlias,
        RelationOneToMany $relation,
        iterable $identifiers
    ): void {
        $context->getQuery()->condition(
            QueryHelper::createKeyCondition(
                $targetTableAlias,
                $relation->getTargetKey(),
                $identifiers
            ),
        );
    }

    /**
     * Key is in the relation source table.
     *
     * "a" is the source, "b" the target.
     *
     * a.target_id -> b.id
     *
     * SELECT b.*
     * FROM b
     * INNER JOIN a ON a.target_id = b.id
     * WHERE a.id = ?
     */
    private function handleKeyInSourceTableRelation(
        EntityQuery $context,
        string $targetTableAlias,
        RelationAnyToOne $relation,
        iterable $identifiers
    ): void {
        $query = $context->getQuery();

        $sourceTableAlias = $context->getNextAlias($relation->getOwner()->getTable()->getName());

        QueryHelper::addReverseJoinStatement(
            $query,
            $relation,
            $sourceTableAlias,
            $targetTableAlias,
            false
        );

        // Create conditions for filtering on the source table.
        $query->condition(
            QueryHelper::createKeyCondition(
                $sourceTableAlias,
                $relation->getSourceKey(),
                $identifiers
            ),
        );
    }

    /**
     * Key is in a mapping table.
     *
     * "a" is the source, "b" the target.
     *
     * "a"."id" -> "mapping"."a_id", "mapping.b_id" -> "b"
     *
     * SELECT b.*
     * FROM b
     * LEFT JOIN mapping ON mapping.b_id = b.id
     * WHERE mapping.a_id = ?
     */
    private function handleKeyInMappingTableRelation(
        EntityQuery $context,
        string $targetTableAlias,
        RelationManyToMany $relation,
        iterable $identifiers
    ): void {
        throw new \Exception("Not implemented yet.");
    }
}

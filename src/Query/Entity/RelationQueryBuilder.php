<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Entity;

use Goat\Mapper\Definition\DefinitionRegistry;
use Goat\Mapper\Definition\Relation;

final class RelationQueryBuilder
{
    /** @var DefinitionRegistry */
    private $definitionRegistry;

    /** @var QueryBuilderFactory */
    private $queryBuilderFactory;

    public function __construct(
        DefinitionRegistry $definitionRegistry,
        QueryBuilderFactory $queryBuilderFactory
    ) {
        $this->definitionRegistry = $definitionRegistry;
        $this->queryBuilderFactory = $queryBuilderFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function createFetchRelatedQuery(
        string $className,
        string $propertyName,
        iterable $identifiers
    ): EntitySelectQuery {

        $relation = $this
            ->definitionRegistry
            ->getRepositoryDefinition($className)
            ->getRelation($propertyName)
        ;

        $entityQuery = $this
            ->queryBuilderFactory
            ->select($relation->getClassName())
        ;

        switch ($relation->getKeyIn()) {

            case Relation::KEY_IN_SOURCE:
                $this->handleKeyInSourceTableRelation(
                    $entityQuery,
                    $propertyName,
                    $relation,
                    $identifiers
                );
                break;

            case Relation::KEY_IN_TARGET:
                $this->handleKeyInTargetTableRelation(
                    $entityQuery,
                    $propertyName,
                    $relation,
                    $identifiers
                );
                break;

            case Relation::KEY_IN_MAPPING:
                $this->handleKeyInMappingTableRelation(
                    $entityQuery,
                    $propertyName,
                    $relation,
                    $identifiers
                );
                break;
        }

        return $entityQuery;
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
        EntitySelectQuery $queryBuilder,
        string $propertyName,
        Relation $relation,
        iterable $identifiers
    ): void {
        $query = $queryBuilder->getSelectQuery();

        $query->condition(
            QueryHelper::createKeyCondition(
                $queryBuilder->getPrimaryTableAlias(),
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
        EntitySelectQuery $queryBuilder,
        string $propertyName,
        Relation $relation,
        iterable $identifiers
    ): void {
        $query = $queryBuilder->getSelectQuery();

        $targetTableAlias = $queryBuilder->getPrimaryTableAlias();
        $sourceTablealias = $queryBuilder->getNextAlias($relation->getSourceTable()->getName());

        QueryHelper::addReverseJoinStatement($query, $relation, $sourceTablealias, $targetTableAlias, false);

        // Create conditions for filtering on the source table.
        $query->condition(
            QueryHelper::createKeyCondition(
                $targetTableAlias,
                $relation->getTargetKey(),
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
        EntitySelectQuery $queryBuilder,
        string $propertyName,
        Relation $relation,
        iterable $identifiers
    ): void {
        throw new \Exception("Not implemented yet.");
    }
}

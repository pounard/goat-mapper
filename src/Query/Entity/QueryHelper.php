<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Entity;

use Goat\Mapper\Definition\Identifier;
use Goat\Mapper\Definition\Key;
use Goat\Mapper\Definition\Relation;
use Goat\Query\ExpressionColumn;
use Goat\Query\ExpressionRelation;
use Goat\Query\SelectQuery;
use Goat\Query\Where;

final class QueryHelper
{
    /**
     * Add relation target table JOIN table on source SELECT query.
     *
     * This handles both simple direct JOIN and mapping table based JOIN
     * depending upon the given relation.
     *
     * @param SelectQuery $query
     * @param Relation $relation
     * @param string $souceTableAlias
     *   The table alias, already existing in the query, on which to join.
     * @param string $targetTableAlias
     *   The table alias, which is not yet in the query, to join.
     * @paramm bool $leftJoin
     *   Per default, this writes an INNER JOIN, it will ba LEFT JOIN if you
     *   pass true here.
     */
    public static function addJoinStatement(
        SelectQuery $query,
        Relation $relation,
        string $sourceTableAlias,
        string $targetTableAlias,
        bool $leftJoin = false
    ): void {
        switch ($relation->getKeyIn()) {

            case Relation::KEY_IN_SOURCE:
            case Relation::KEY_IN_TARGET:
                self::addJoinStatementDirect($query, $relation, $sourceTableAlias, $targetTableAlias, $leftJoin);
                break;

            case Relation::KEY_IN_MAPPING:
                self::addJoinStatementWithMappingTable($query, $relation, $sourceTableAlias, $targetTableAlias, $leftJoin);
                break;
        }
    }

    /**
     * Writes a direct JOIN statement.
     */
    private static function addJoinStatementDirect(
        SelectQuery $query,
        Relation $relation,
        string $sourceTableAlias,
        string $targetTableAlias,
        bool $leftJoin = false
    ): void {
        $table = $relation->getTargetTable();
        $tableExpression = ExpressionRelation::create($table->getName(), $targetTableAlias, $table->getSchema());

        $targetKeyColumnsMap = $relation->getTargetKey()->getColumnNames();
        $sourceKeyColumnsMap = $relation->getSourceKey()->getColumnNames();

        $joinConditions = (new Where());
        foreach ($targetKeyColumnsMap as $i => $columnName) {
            $joinConditions->isEqual(
                ExpressionColumn::create($columnName, $targetTableAlias),
                ExpressionColumn::create($sourceKeyColumnsMap[$i], $sourceTableAlias)
            );
        }

        if ($leftJoin) {
            $query->leftJoin($tableExpression, $joinConditions);
        } else {
            $query->innerJoin($tableExpression, $joinConditions);
        }
    }

    /**
     * Write a JOIN statement using a mapping table.
     */
    private static function addJoinStatementWithMappingTable(
        SelectQuery $query,
        Relation $relation,
        string $sourceTableAlias,
        string $targetTableAlias,
        bool $leftJoin = false
    ): void {
        throw new \Exception("Not implemented yet.");
    }

    /**
     * Add relation source table JOIN table on target SELECT query.
     *
     * This handles both simple direct JOIN and mapping table based JOIN
     * depending upon the given relation.
     *
     * @param SelectQuery $query
     * @param Relation $relation
     * @param string $souceTableAlias
     *   The table alias, already existing in the query, on which to join.
     * @param string $targetTableAlias
     *   The table alias, which is not yet in the query, to join.
     * @paramm bool $leftJoin
     *   Per default, this writes an INNER JOIN, it will ba LEFT JOIN if you
     *   pass true here.
     */
    public static function addReverseJoinStatement(
        SelectQuery $query,
        Relation $relation,
        string $sourceTableAlias,
        string $targetTableAlias,
        bool $leftJoin = false
    ): void {
        switch ($relation->getKeyIn()) {

            case Relation::KEY_IN_SOURCE:
            case Relation::KEY_IN_TARGET:
                self::addReverseJoinStatementDirect($query, $relation, $sourceTableAlias, $targetTableAlias, $leftJoin);
                break;

            case Relation::KEY_IN_MAPPING:
                self::addReverseJoinStatementWithMappingTable($query, $relation, $sourceTableAlias, $targetTableAlias, $leftJoin);
                break;
        }
    }

    /**
     * Writes a direct JOIN statement.
     */
    private static function addReverseJoinStatementDirect(
        SelectQuery $query,
        Relation $relation,
        string $sourceTableAlias,
        string $targetTableAlias,
        bool $leftJoin = false
    ): void {
        $table = $relation->getSourceTable();
        $tableExpression = ExpressionRelation::create($table->getName(), $sourceTableAlias, $table->getSchema());

        $targetKeyColumnsMap = $relation->getTargetKey()->getColumnNames();
        $sourceKeyColumnsMap = $relation->getSourceKey()->getColumnNames();

        $joinConditions = (new Where());
        foreach ($targetKeyColumnsMap as $i => $columnName) {
            $joinConditions->isEqual(
                ExpressionColumn::create($columnName, $targetTableAlias),
                ExpressionColumn::create($sourceKeyColumnsMap[$i], $sourceTableAlias)
            );
        }

        if ($leftJoin) {
            $query->leftJoin($tableExpression, $joinConditions);
        } else {
            $query->innerJoin($tableExpression, $joinConditions);
        }
    }

    /**
     * Write a JOIN statement using a mapping table.
     */
    private static function addReverseJoinStatementWithMappingTable(
        SelectQuery $query,
        Relation $relation,
        string $sourceTableAlias,
        string $targetTableAlias,
        bool $leftJoin = false
    ): void {
        throw new \Exception("Not implemented yet.");
    }

    /**
     * Writes SQL condition for matching the given identifier list to the
     * given key, using OR or IN statements.
     */
    public static function createKeyCondition(string $tableAlias, Key $key, iterable $identifiers): Where
    {
        if (1 === $key->count()) {
            return self::createKeyConditionWithSingleColumn($tableAlias, $key, $identifiers);
        } else {
            return self::createKeyConditionWithMultipleColumns($tableAlias, $key, $identifiers);
        }
    }

    /**
     * Writes a COL1 IN (ID1,...) expression for the given key matching
     * the given identifier list.
     */
    private static function createKeyConditionWithSingleColumn(string $tableAlias, Key $key, iterable $identifiers): Where
    {
        $where = new Where();

        $in = [];
        foreach ($identifiers as $id) {
            \assert($id instanceof Identifier);

            $id->failIfNotCompatible($key);

            $in[] = $id->toArray()[0];
        }

        return $where->isIn(
            ExpressionColumn::create(
                $key->getColumnNames()[0],
                $tableAlias
            ),
            $in
        );
    }

    /**
     * Writes a (COL1 = ID1 AND COL2 = ID2, ...) OR ... expression for the
     * given key matching the given identifier list.
     */
    private static function createKeyConditionWithMultipleColumns(string $tableAlias, Key $key, iterable $identifiers): Where
    {
        $where = new Where(Where::OR);

        foreach ($identifiers as $id) {
            \assert($id instanceof Identifier);

            $id->failIfNotCompatible($key);

            $keyWhere = new Where(Where::AND);

            $columnNames = $key->getColumnNames();
            foreach ($id->toArray() as $index => $value) {
                $keyWhere->isEqual(
                    ExpressionColumn::create($columnNames[$index], $tableAlias),
                    $value,
                );
            }

            $where->condition($keyWhere);
        }

        return $where;
    }
}

<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Entity;

use Goat\Mapper\Definition\Identifier;
use Goat\Mapper\Definition\Key;
use Goat\Query\ExpressionColumn;
use Goat\Query\Where;

final class QueryHelper
{
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

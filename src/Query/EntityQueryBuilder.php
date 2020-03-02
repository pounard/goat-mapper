<?php

declare(strict_types=1);

namespace Goat\Mapper\Query;

use Goat\Mapper\Repository;
use Goat\Mapper\Definition\Key;
use Goat\Query\QueryError;

class EntityQueryBuilder
{
    /** @var Repository */
    private $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Expand the given key as a key-value pairs suitable for SELECT query,
     * keys being column names, values the associated set of values.
     *
     * @param \Goat\Mapper\Definition\Key $key
     * @param mixed $id
     *   It can be any value if the key as a single column, otherwise it must
     *   be an array of values, ordered in the same order as the key definition.
     * @param null|string $tableOrAlias
     *   Optional SELECT FROM table alias.
     *
     * @throws \Goat\Query\QueryError
     *   If given argument column count mismatches with key.
     *
     * @return array
     *   Keys are column names, aliased if necessary, for WHERE conditions,
     *   values are anything that Goat\Query\SelectQuery can consume as values
     *   for WHERE clause.
     *
     * @see \Goat\Query\SelectQuery
     */
    public static function expandKey(Key $key, $id, ?string $tableOrAlias = null): array
    {
        if (!\is_array($id)) {
            $id = [$id];
        } else {
            $id = \array_values($id);
        }
        if (\count($id) !== $key->count()) {
            throw new QueryError(\sprintf(
                "Column count mismatch between key and user input, awaiting columns (in that order): '%s'",
                \implode("', '", $key->getColumnNames()))
            );
        }

        $ret = [];

        foreach ($key->getColumnNames() as $i => $name) {
            // Repository can choose to actually already have prefixed the column
            // primary key using the alias, let's cover this use case too: this
            // might happen if either the original select query do need
            // deambiguation from the start, or if the API user was extra
            // precautionous.
            if ($tableOrAlias && false === \strpos($name, '.')) {
                $ret[$tableOrAlias.'.'.$name] = $id[$i];
            } else {
                $ret[$name] = $id[$i];
            }
        }

        return $ret;
    }

    /**
     * Create and get a SELECT query builder for this repository.
     */
    public function fetch(): EntityFetchQueryBuilder
    {
        return new EntityFetchQueryBuilder($this->repository);
    }

    /**
     * Create and get an UPDATE query builder for this repository.
     */
    public function update(): EntityUpdateQueryBuilder
    {
        return new EntityUpdateQueryBuilder($this->repository);
    }
}

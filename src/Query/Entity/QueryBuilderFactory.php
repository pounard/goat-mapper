<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Entity;

use Goat\Mapper\Definition\Identifier;
use Goat\Mapper\Definition\Key;
use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use Goat\Mapper\Hydration\EntityHydrator\EntityHydratorFactory;
use Goat\Mapper\Query\Graph\EntityQuery;
use Goat\Query\QueryError;
use Goat\Runner\Runner;

class QueryBuilderFactory
{
    private Runner $runner;
    private DefinitionRegistry $definitionRegistry;
    private EntityHydratorFactory $entityHydratorFactory;

    public function __construct(
        Runner $runner,
        DefinitionRegistry $definitionRegistry,
        EntityHydratorFactory $entityHydratorFactory
    ) {
        $this->runner = $runner;
        $this->definitionRegistry = $definitionRegistry;
        $this->entityHydratorFactory = $entityHydratorFactory;
    }

    /**
     * Expand the given key as a key-value pairs suitable for SELECT query,
     * keys being column names, values the associated set of values.
     *
     * @param \Goat\Mapper\Definition\Key $key
     * @param mixed|mixed[]|Identifier $id
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
        $id = Identifier::normalize($id);

        if (!$id->isCompatible($key)) {
            throw new QueryError(\sprintf(
                "Column count mismatch between key and user input, awaiting columns (in that order): '%s'",
                \implode("', '", $key->getColumnNames()))
            );
        }

        $ret = [];
        $values = $id->toArray();

        foreach ($key->getColumnNames() as $i => $name) {
            // Repository can choose to actually already have prefixed the column
            // primary key using the alias, let's cover this use case too: this
            // might happen if either the original select query do need
            // deambiguation from the start, or if the API user was extra
            // precautionous.
            if ($tableOrAlias && false === \strpos($name, '.')) {
                $ret[$tableOrAlias.'.'.$name] = $values[$i];
            } else {
                $ret[$name] = $values[$i];
            }
        }

        return $ret;
    }

    /**
     * Create and get a SELECT query builder for this repository.
     */
    public function query(string $className, ?string $primaryTableAlias = null): EntityQuery
    {
        return new EntityQuery(
            $this->definitionRegistry,
            $this->entityHydratorFactory,
            $this->runner,
            $className,
            $primaryTableAlias
        );
    }

    /**
     * Alias for query, but preparing the query for loading relations.
     *
     * @param Identifier[] $identifiers
     */
    public function related(string $className, string $propertyName, iterable $identifiers)
    {
        $relation = $this
            ->definitionRegistry
            ->getDefinition($className)
            ->getRelation($propertyName)
        ;

        return $this
            ->query($relation->getClassName())
            ->from($className, $propertyName, $identifiers)
        ;
    }

    /**
     * Create and get an UPDATE query builder for this repository.
     */
    public function update(string $className): EntityUpdateQueryBuilder
    {
        throw new \Exception("Not implemented yet.");
    }
}

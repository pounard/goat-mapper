<?php

declare(strict_types=1);

namespace Goat\Mapper\Query;

use Goat\Mapper\Repository;
use Goat\Mapper\Definition\RepositoryDefinition;
use Goat\Mapper\Error\PropertyDoesNotExistError;
use Goat\Mapper\Error\PropertyError;
use Goat\Query\ExpressionRelation;
use Goat\Query\SelectQuery;
use Goat\Query\UpdateQuery;
use Goat\Runner\ResultIterator;

class EntityUpdateQueryBuilder
{
    /** @var Repository */
    private $repository;

    /** @var RepositoryDefinition */
    private $definition;

    /** @var SelectQuery */
    private $query;

    public function __construct(Repository $repository, ?string $primaryTableAlias = null)
    {
        $this->repository = $repository;
        $this->definition = $repository->getRepositoryDefinition();
    }

    /**
     * Add a criteria for selecting entities.
     *
     * @param callable|string $callbackOrProperty
     *
     *   This can be either of:
     *
     *     - a callback, whose first arguments are respectively:
     *         - \Goat\Query\Where $where
     *         - \Goat\Query\SelectQuery $select
     *
     *     - a target class name property name, it will be transparently
     *       replaced using the rightful column name, prefixed with its table
     *       alias; column can belong to any of the FROM or JOIN clauses, in
     *       case of a conflict, FROM will be used, in cause a conflict in FROM
     *       (table cartesian product) an exception will be thrown,
     *
     *     - any of the select query column names, case in which you probably
     *       should be aware that table aliases can be generated.
     *
     *     - anything else that SelectQuery::expression() accepts.
     *
     * @param mixed $value
     *   Any value, from a scalar, to an object, to goat-query expression.
     *
     * @return $this
     *
     * @throws PropertyDoesNotExistError
     *   If property is a string and does not exists.
     * @throws PropertyError
     *   If property is a callback, and value was specified.
     */
    public function condition($propertyNameOrCallack, $value = null): self
    {
        if (\is_string($propertyNameOrCallack)) {
            if ($columnName = $this->definition->findColumnName($propertyNameOrCallack)) {
                $propertyNameOrCallack = $columnName;
            }
        }

        $this->getQuery()->condition($propertyNameOrCallack, $value);

        return $this;
    }

    /**
     * Set value.
     *
     * @param string $property
     *   Entity property name or column name.
     * @param mixed $value
     *   Any value, or SQL \Goat\Query\Expression object.
     *
     * @return $this
     */
    public function set($propertyName, $value): self
    {
        $this->getQuery()->set(
            $this->definition->findColumnName($propertyName) ?? $propertyName,
            $value
        );

        return $this;
    }

    /**
     * Set multiple values.
     *
     * @param array<string,mixed> $values
     *   Keys are entity property name or column name.
     *   Values are any value, or SQL \Goat\Query\Expression object.
     *
     * @return self
     */
    public function sets(array $values): self
    {
        $query = $this->getQuery();

        foreach ($values as $propertyName => $value) {
            $query->set(
                $this->definition->findColumnName($propertyName)  ?? $propertyName,
                $value
            );
        }

        return $this;
    }

    /**
     * Fetch the build select query, you can then call execute() to fetch data.
     */
    public function build(): UpdateQuery
    {
        return $this->getQuery();
    }

    /**
     * Alias of calling self::build()->execute().
     */
    public function execute(): ResultIterator
    {
        return $this->build()->execute();
    }

    private function getQuery(): UpdateQuery
    {
        return $this->query ?? (
            $this->query = $this->createUpdate()
        );
    }

    private function createUpdate(): UpdateQuery
    {
        $table = $this->definition->getTable();

        $relation = ExpressionRelation::create($table->getName(), null, $table->getSchema());

        $query = $this->repository->getRunner()->getQueryBuilder()->update($relation);

        return $query;
    }
}

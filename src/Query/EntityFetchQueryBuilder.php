<?php

declare(strict_types=1);

namespace Goat\Mapper\Query;

use Goat\Mapper\Repository;
use Goat\Mapper\Definition\Relation;
use Goat\Mapper\Definition\RepositoryDefinition;
use Goat\Mapper\Error\PropertyDoesNotExistError;
use Goat\Mapper\Error\PropertyError;
use Goat\Mapper\Error\RelationDoesNotExistError;
use Goat\Mapper\Hydration\EntityHydrator\EntityHydratorFactory;
use Goat\Query\ExpressionColumn;
use Goat\Query\ExpressionRelation;
use Goat\Query\QueryError;
use Goat\Query\SelectQuery;
use Goat\Query\Where;
use Goat\Runner\ResultIterator;

/**
 * Entity-focused SELECT query builder.
 *
 * Using method on this builder instead of a raw SQL SelectQuery gives you
 * the possibility to write conditions and statements using entity object
 * property names instead of database table columns.
 *
 * This class is one of the most important of all: it holds most of the magic
 * to SELECT FROM entity tables, and handles the eager versus lazy fetching
 * logic.
 *
 * Loaded objects will always be proxy instances as soon as relations exists:
 *
 *   - for eagerly loaded relations (any to one) the proxy will simply handle
 *     the related object hydration when necessary,
 *
 *   - for lazy loaded relations (any to many) relations, loaded entities
 *     will be ghost objects, which will be fully loaded upon access,
 *
 *   - for lazy loaded relations (any to one), the proxy will create an entity
 *     query for lazyly loaded another entity, which at their turn will
 *     recursively be proxies with the same behaviour.
 */
class EntityFetchQueryBuilder
{
    /** @var array<string,int> */
    private $aliases = [];

    /** @var EntityHydratorFactory */
    private $entityHydratorFactory;

    /** @var Repository */
    private $repository;

    /** @var RepositoryDefinition */
    private $definition;

    /** @var bool */
    private $withColumns = true;

    /** @var string */
    private $primaryTableAlias;

    /** @var SelectQuery */
    private $query;

    /** @var string[] */
    // @todo set all one to one or many to one being default here
    private $eagerRelations = [];

    /** @var string[] */
    // @todo set all many to many or one to many being default here
    private $lazyRelations = [];

    public function __construct(EntityHydratorFactory $entityHydratorFactory, Repository $repository, ?string $primaryTableAlias = null)
    {
        $this->repository = $repository;
        $this->definition = $repository->getRepositoryDefinition();

        $this->entityHydratorFactory = $entityHydratorFactory;

        $this->primaryTableAlias = $this->getNextAlias($primaryTableAlias ?? $this->definition->getTable()->getName());
    }

    /**
     * Force a relation values to be loaded eargly:
     *
     *   - if relation is one to one or many to one, a simple JOIN on the select
     *     query will be done,
     *
     *   - if relation is many to many or one to many, an additional SELECT
     *     query will be done, your objects you will be forced-loaded in order
     *     to match with the additional SELECT query results, and the result
     *     iterator returned will be a rewindable decorator of it.
     *
     * @param string $relation
     *   Anything that RepositoryDefinition::getRelation() accepts.
     *
     * @return $this
     *
     * @throws RelationDoesNotExistError
     *   If property does not exist or is not a relation.
     *
     * @see RepositoryDefinition
     *   For $relation parameter definition.
     */
    public function eager(string $relation): self
    {
        $definition = $this->definition->getRelation($relation);

        // @todo add "required" or "optional" (left join or inner join)
        // @todo once all will be supported, remove the switch
        switch ($definition->getMode()) {

            case Relation::MODE_ONE_TO_ONE:
            case Relation::MODE_MANY_TO_ONE:
                $propertyName = $definition->getPropertyName();
                $this->eagerRelations[$propertyName] = true;
                // Deactivate lazy fetch for this relation.
                $this->lazyRelations[$propertyName] = false;
                break;

            case Relation::MODE_ONE_TO_MANY:
                // @todo needs extra SQL query and rewindable result iterator
                throw new QueryError("One to many eager fetch is not implemented yet.");

            case Relation::MODE_MANY_TO_MANY:
                // @todo needs extra SQL query and rewindable result iterator
                throw new QueryError("Many to many eager fetch is not implemented yet.");
        }

        return $this;
    }

    /**
     * For a relation to be lazy.
     *
     * @param string $relation
     *   Anything that RepositoryDefinition::getRelation() accepts.
     *
     * @return $this
     *
     * @throws RelationDoesNotExistError
     *   If property does not exist or is not a relation.
     *
     * @see RepositoryDefinition
     *   For $relation parameter definition.
     */
    public function lazy(string $relation): self
    {
        throw new \Exception("Implement me");
    }

    /**
     * Force a relation to be dropped/ignored (hydrated objects will be partial).
     *
     * @param string $relation
     *   Anything that RepositoryDefinition::getRelation() accepts.
     *
     * @return $this
     *
     * @throws RelationDoesNotExistError
     *   If property does not exist or is not a relation.
     *
     * @see RepositoryDefinition
     *   For $relation parameter definition.
     */
    public function drop(string $relation): self
    {
        throw new \Exception("Implement me");
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
     *       case of a conflict, FROM will be used, in case of conflict in FROM
     *       (table cartesian product) an exception will be thrown,
     *       @todo Implement this.
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
                $propertyNameOrCallack = ExpressionColumn::create($columnName, $this->primaryTableAlias);
            }
        }

        $this->getQuery()->condition($propertyNameOrCallack, $value);

        return $this;
    }

    /**
     * Toggle with columns, default is true, if set to false there will not
     * be any columns in select, you'll have to set them yourself.
     *
     * @return $this
     */
    public function withColumns(bool $toggle): self
    {
        $this->withColumns = $toggle;

        return $this;
    }

    private function addRelationColumns(SelectQuery $query, string $propertyName, Relation $relation, string $tableAlias): void
    {
        $targetEntityDefinition = $this
            ->repository
            ->getRelatedRepository($propertyName)
            ->getRepositoryDefinition()
            ->getEntityDefinition()
        ;

        // Add related object columns to SELECT clause. They will be prefixed
        // using the property name and a dot, which will allow the custom
        // hydrator to handled nested objects hydration.
        // @todo make the nested hydrator really lazy using a proxy object.
        if ($columns = $targetEntityDefinition->getColumnMap()) {
            foreach ($columns as $targetPropertyName => $columName) {
                $query->column(
                    ExpressionColumn::create($columName, $tableAlias),
                    $propertyName.'.'.$targetPropertyName
                );
            }
        } else {
            // @todo I think this will not work, and we need to know the columns to
            //   be able to do that...
            // @todo exclude relations from that mapping, and allow it to be at least
            //   lazy itself.
            $query->column(ExpressionColumn::create('*', $tableAlias), $propertyName);
        }

        // @todo prepare hydrator to hydrate nested.
        // @todo handle potential name conflicts.
    }

    private function handleEagerToOneInSourceTableRelation(SelectQuery $query, string $propertyName, Relation $relation): void
    {
        $table = $relation->getTable();
        $alias = $this->getNextAlias($table->getName());
        $tableExpression = ExpressionRelation::create($table->getName(), $alias, $table->getSchema());

        $targetKeyColumns = $relation->getTargetKey();
        $sourceKeyColumnsMap = $relation->getSourceKey()->getColumnNames();

        $joinConditions = (new Where());
        foreach ($targetKeyColumns->getColumnNames() as $i => $columnName) {
            $joinConditions->isEqual(
                ExpressionColumn::create($columnName, $alias),
                ExpressionColumn::create($sourceKeyColumnsMap[$i], $this->primaryTableAlias)
            );
        }

        // We will always use a LEFT JOIN to avoid ghosting existing source
        // relation objects from missing target entity. Even when the relation
        // is required, we cannot let broken making our entity invisible to our
        // users.
        $query->leftJoin($tableExpression, $joinConditions);

        $this->addRelationColumns($query, $propertyName, $relation, $alias);
    }

    private function handleEagerToOneInTargetTableRelation(SelectQuery $query, string $propertyName, Relation $relation): void
    {
        $table = $relation->getTable();
        $alias = $this->getNextAlias($table->getName());
        $tableExpression = ExpressionRelation::create($table->getName(), $alias, $table->getSchema());

        $targetKeyColumns = $relation->getTargetKey();
        $sourceKeyColumnsMap = $this->definition->getPrimaryKey()->getColumnNames();

        $joinConditions = (new Where());
        foreach ($targetKeyColumns->getColumnNames() as $i => $columnName) {
            $joinConditions->isEqual(
                ExpressionColumn::create($columnName, $alias),
                ExpressionColumn::create($sourceKeyColumnsMap[$i], $this->primaryTableAlias)
            );
        }

        // We will always use a LEFT JOIN to avoid ghosting existing source
        // relation objects from missing target entity. Even when the relation
        // is required, we cannot let broken making our entity invisible to our
        // users.
        $query->leftJoin($tableExpression, $joinConditions);

        $this->addRelationColumns($query, $propertyName, $relation, $alias);
    }

    private function handleEagerToOneInMappingTableRelation(SelectQuery $query, string $propertyName, Relation $relation): void
    {
        throw new \Exception("Not implemented yet.");
    }

    private function handleEagerToOneRelation(SelectQuery $query, string $propertyName, Relation $relation): void
    {
        switch ($relation->getKeyIn()) {

            case Relation::KEY_IN_MAPPING:
                $this->handleEagerToOneInMappingTableRelation($query, $propertyName, $relation);
                break;

            case Relation::KEY_IN_TARGET:
                $this->handleEagerToOneInTargetTableRelation($query, $propertyName, $relation);
                break;

            case Relation::KEY_IN_SOURCE:
                $this->handleEagerToOneInSourceTableRelation($query, $propertyName, $relation);
                break;
        }
    }

    private function handleEagerToManyWithKeyInTargetTableRelation(SelectQuery $query, string $propertyName, Relation $relation): void
    {
        throw new \Exception("Not implemented yet.");
    }

    private function handleEagerToManyWithMappingTableRelation(SelectQuery $query, string $propertyName, Relation $relation): void
    {
        throw new \Exception("Not implemented yet.");
    }

    private function handleEagerToManyRelation(SelectQuery $query, string $propertyName, Relation $relation): void
    {
        switch ($relation->getKeyIn()) {

            case Relation::KEY_IN_MAPPING:
                $this->handleEagerToManyWithMappingTableRelation($query, $propertyName, $relation);
                break;

            case Relation::KEY_IN_TARGET:
                $this->handleEagerToManyWithKeyInTargetTableRelation($query, $propertyName, $relation);
                break;

            default:
                throw new \Exception("Target entity primary key can only be in a mapping table or in the target table for any to many relations.");
        }
    }

    private function handleEagerRelation(SelectQuery $query, string $propertyName, Relation $relation): void
    {
        if ($relation->isMultiple()) {
            $this->handleEagerToManyRelation($query, $propertyName, $relation);
        } else {
            $this->handleEagerToOneRelation($query, $propertyName, $relation);
        }
    }

    /**
     * Fetch the build select query, you can then call execute() to fetch data.
     */
    public function build(): SelectQuery
    {
        $query = $this->getQuery();

        $entityDefinition = $this->definition->getEntityDefinition();
        $lazyPropertyNames = [];

        if ($this->withColumns) {
            if ($columnMap = $entityDefinition->getColumnMap()) {
                foreach ($columnMap as $propertyName => $columnName) {
                    $query->column(
                        ExpressionColumn::create($columnName, $this->primaryTableAlias),
                        $propertyName
                    );
                }
            } else {
                $query->column(ExpressionColumn::create('*', $this->primaryTableAlias));
            }

            $query->setOption('class', $entityDefinition->getClassName());

            // @todo Not sure this should be restored...
            // $select->setOption('types', $this->defineSelectColumnsTypes());

            foreach ($this->repository->getRepositoryDefinition()->getRelations() as $relation) {
                $propertyName = $relation->getPropertyName();
                if ($this->eagerRelations[$propertyName] ?? false) {
                    $this->handleEagerRelation($query, $propertyName, $relation);
                } else {
                    $lazyPropertyNames[] = $propertyName;
                }
            }

            // @todo handle lazy to one relations
            // @todo handle lazy to many relations
        }

        $query->setOption(
            'hydrator',
            $this
                ->entityHydratorFactory
                ->createHydrator(
                    $this->repository,
                    $lazyPropertyNames
                )
        );

        return $query;
    }

    /**
     * Alias of calling self::build()->execute().
     */
    public function execute(): ResultIterator
    {
        if (!$this->withColumns) {
            throw new QueryError("You cannot execute a repository select query without selected columns");
        }

        return $this->build()->execute();
    }

    /**
     * Get primary table SQL alias in SELECT query.
     */
    public function getPrimaryTableAlias(): string
    {
        return $this->primaryTableAlias;
    }

    private function getNextAlias(string $alias): string
    {
        if (isset($this->aliases[$alias])) {
            return $alias.'_'.(++$this->aliases[$alias]);
        }
        $this->aliases[$alias] = 0;

        return $alias;
    }

    private function getQuery(): SelectQuery
    {
        return $this->query ?? (
            $this->query = $this->createSelect()
        );
    }

    private function createSelect(): SelectQuery
    {
        $table = $this->definition->getTable();

        $relation = ExpressionRelation::create(
            $table->getName(),
            $this->primaryTableAlias,
            $table->getSchema()
        );

        return $this
            ->repository
            ->getRunner()
            ->getQueryBuilder()
            ->select($relation)
        ;
    }
}

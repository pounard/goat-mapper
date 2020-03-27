<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Entity;

use Goat\Mapper\Definition\Relation;
use Goat\Mapper\Definition\RepositoryDefinition;
use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use Goat\Mapper\Error\PropertyDoesNotExistError;
use Goat\Mapper\Error\PropertyError;
use Goat\Mapper\Error\QueryError;
use Goat\Mapper\Error\RelationDoesNotExistError;
use Goat\Mapper\Hydration\EntityHydrator\EntityHydratorContext;
use Goat\Mapper\Hydration\EntityHydrator\EntityHydratorFactory;
use Goat\Mapper\Query\Relation\DefaultRelationFetcher;
use Goat\Query\ExpressionColumn;
use Goat\Query\ExpressionRelation;
use Goat\Query\SelectQuery;
use Goat\Runner\ResultIterator;
use Goat\Runner\Runner;

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
class EntitySelectQuery
{
    /** @var array<string,int> */
    private $aliases = [];

    /** @var string */
    private $className;

    /** @var EntityHydratorFactory */
    private $entityHydratorFactory;

    /** @var RelationQueryBuilder */
    private $relationQueryBuilder;

    /** @var DefinitionRegistry */
    private $definitionRegistry;

    /** @var RepositoryDefinition */
    private $definition;

    /** @var bool */
    private $withColumns = true;

    /** @var string */
    private $primaryTableAlias;

    /** @var Runner */
    private $runner;

    /** @var SelectQuery */
    private $query;

    /** @var string[] */
    private $eagerRelations = [];

    public function __construct(
        Runner $runner,
        DefinitionRegistry $definitionRegistry,
        EntityHydratorFactory $entityHydratorFactory,
        RelationQueryBuilder $relationQueryBuilder,
        string $className,
        ?string $primaryTableAlias = null
    ) {
        $this->runner = $runner;
        $this->className = $className;
        $this->definitionRegistry = $definitionRegistry;
        $this->definition = $definitionRegistry->getDefinition($className);
        $this->entityHydratorFactory = $entityHydratorFactory;
        $this->relationQueryBuilder = $relationQueryBuilder;
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
        unset(
            $this->eagerRelations[
                $this
                    ->definition
                    ->getRelation($relation)
                    ->getPropertyName()
            ]
        );
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
            if ($columnName = $this->definition->getColumn($propertyNameOrCallack)) {
                $propertyNameOrCallack = ExpressionColumn::create($columnName, $this->primaryTableAlias);
            }
        }

        $this->getSelectQuery()->condition($propertyNameOrCallack, $value);

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

    /**
     * Add repository columns to the given select query.
     */
    private function addEntityColumns(SelectQuery $query, string $className, string $tableAlias, ?string $propertyName = null): void
    {
        $targetDefinition = $this
            ->definitionRegistry
            ->getDefinition(
                $className
            )
        ;

        // Add related object columns to SELECT clause. They will be prefixed
        // using the property name and a dot, which will allow the custom
        // hydrator to handled nested objects hydration.
        // @todo make the nested hydrator really lazy using a proxy object.
        if ($columns = $targetDefinition->getColumnMap()) {
            foreach ($columns as $targetPropertyName => $columName) {
                $query->column(
                    ExpressionColumn::create($columName, $tableAlias),
                    $propertyName ? $propertyName.'.'.$targetPropertyName : $targetPropertyName
                );
            }
        } else {
            throw new QueryError("Cannot eargerly fetch a related entity whose properties are not defined.");
        }

        // @todo Not sure this should be restored...
        // $select->setOption('types', $this->defineSelectColumnsTypes());
        // @todo prepare hydrator to hydrate nested.
        // @todo handle potential name conflicts.
    }

    private function addRelationColumns(
        SelectQuery $query,
        Relation $relation,
        string $targetTableAlias
    ): void {
        $propertyName = $relation->getPropertyName();

        self::addEntityColumns(
            $query,
            $this
                ->definition
                ->getRelation($propertyName)
                ->getClassName(),
            $targetTableAlias,
            $propertyName
        );
    }

    private function handleEagerToOneRelation(SelectQuery $query, Relation $relation): void
    {
        $targetTableAlias = $this->getNextAlias($relation->getTargetTable()->getName());

        // We will always use a LEFT JOIN to avoid ghosting existing source
        // relation objects from missing target entity. Even when the relation
        // is required, we cannot let broken making our entity invisible to our
        // users.
        QueryHelper::addJoinStatement($query, $relation, $this->primaryTableAlias, $targetTableAlias, true);
        $this->addRelationColumns($query, $relation, $targetTableAlias);
    }

    private function handleEagerToManyWithKeyInTargetTableRelation(SelectQuery $query, Relation $relation): void
    {
        throw new \Exception("Not implemented yet.");
    }

    private function handleEagerToManyWithMappingTableRelation(SelectQuery $query, Relation $relation): void
    {
        throw new \Exception("Not implemented yet.");
    }

    private function handleEagerToManyRelation(SelectQuery $query, Relation $relation): void
    {
        switch ($relation->getKeyIn()) {

            case Relation::KEY_IN_MAPPING:
                $this->handleEagerToManyWithMappingTableRelation($query, $relation);
                break;

            case Relation::KEY_IN_TARGET:
                $this->handleEagerToManyWithKeyInTargetTableRelation($query, $relation);
                break;

            default:
                throw new \Exception("Target entity primary key can only be in a mapping table or in the target table for any to many relations.");
        }
    }

    private function handleEagerRelation(SelectQuery $query, Relation $relation): void
    {
        if ($relation->isMultiple()) {
            $this->handleEagerToManyRelation($query, $relation);
        } else {
            $this->handleEagerToOneRelation($query, $relation);
        }
    }

    /**
     * Fetch the build select query, you can then call execute() to fetch data.
     */
    public function build(): SelectQuery
    {
        $query = $this->getSelectQuery();
        $context = new EntityHydratorContext($this->className);

        if ($this->withColumns) {

            $this->addEntityColumns(
                $query,
                $this->className,
                $this->primaryTableAlias
            );

            $query->setOption('class', $this->className);

            foreach ($this->definition->getRelations() as $relation) {
                $propertyName = $relation->getPropertyName();

                if ($this->eagerRelations[$propertyName] ?? false) {
                    $this->handleEagerRelation($query, $relation);
                } else {
                    $context->lazyPropertyNames[] = $propertyName;
                }
            }
        }

        if ($context->lazyPropertyNames) {
            $context->relationFetcher = new DefaultRelationFetcher(
                $this->relationQueryBuilder
            );
        }

        $entityHydrator = $this
            ->entityHydratorFactory
            ->createHydrator(
                $context
            )
        ;

        $primaryKey = $this->definition->getPrimaryKey();

        $query->setOption(
            'hydrator',
            static function (array $values) use ($primaryKey, $entityHydrator) {
                return $entityHydrator(
                    $primaryKey->createIdentifierFromRow($values),
                    $values
                );
            }
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

    public function getNextAlias(string $alias): string
    {
        if (isset($this->aliases[$alias])) {
            return $alias.'_'.(++$this->aliases[$alias]);
        }
        $this->aliases[$alias] = 0;

        return $alias;
    }

    public function getSelectQuery(): SelectQuery
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
            ->runner
            ->getQueryBuilder()
            ->select($relation)
        ;
    }
}

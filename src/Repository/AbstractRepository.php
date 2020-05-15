<?php

declare(strict_types=1);

namespace Goat\Mapper\Repository;

use Goat\Mapper\EntityManager;
use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Error\QueryError;
use Goat\Mapper\Query\Entity\EntityQuery;
use Goat\Mapper\Query\Relation\DefaultRelationFetcher;
use Goat\Query\DeleteQuery;
use Goat\Query\ExpressionColumn;
use Goat\Query\ExpressionRelation;
use Goat\Query\InsertQuery;
use Goat\Query\MergeQuery;
use Goat\Query\Query;
use Goat\Query\UpdateQuery;
use Goat\Runner\Runner;

/**
 * @var Repository<T>
 */
abstract class AbstractRepository implements Repository
{
    private Entity $definition;
    private EntityManager $manager;
    private string $className;

    public function __construct(string $className, EntityManager $manager)
    {
        $this->definition = $manager->getDefinitionRegistry()->getDefinition($className);
        $this->className = $className;
        $this->manager = $manager;
    }

    /**
     * Get entity manager.
     */
    protected final function getEntityManager(): EntityManager
    {
        return $this->manager;
    }

    /**
     * Get database connection runner.
     */
    protected final function getRunner(): Runner
    {
        return $this->manager->getRunner();
    }

    /**
     * Get table expression.
     */
    protected final function getRelation(?string $primaryTableAlias = null): ExpressionRelation
    {
        $table = $this->getDefinition()->getTable();

        return ExpressionRelation::create(
            $table->getName(),
            $primaryTableAlias,
            $table->getSchema()
        );
    }

    /**
     * Add RETURNING clause on query.
     */
    protected final function addQueryReturningClause(Query $query, ?string $primaryTableAlias = null): void
    {
        if (!$query instanceof UpdateQuery &&
            !$query instanceof DeleteQuery &&
            !$query instanceof InsertQuery &&
            !$query instanceof MergeQuery
        ) {
            throw new QueryError(\sprintf(
                "Cannot add RETURNING clause on query of type %s",
                \get_class($query)
            ));
        }

        foreach ($this->getDefinition()->getColumnMap() as $propertyName => $columnName) {

            if ($primaryTableAlias) {
                $column = ExpressionColumn::create($columnName, $primaryTableAlias);
            } else {
                $column = ExpressionColumn::create($columnName);
            }

            if ($propertyName === $columnName) {
                $query->returning($column);
            } else {
                $query->returning($column, $propertyName);
            }
        }
    }

    /**
     * Prepare entity hydrator for this query.
     *
     * The prepared hydrator will not eager load anything.
     */
    protected final function addQueryEntityHydrator(Query $query, ?string $alias = null): void
    {
        $entityHydrator = $this
            ->manager
            ->getQueryBuilderFactory()
            ->getEntityHydratorFactory()
            ->createHydrator(
                $this->className
            )
        ;

        $fetcher = new DefaultRelationFetcher(
            $this
                ->manager
                ->getQueryBuilderFactory()
        );

        $query->setOption(
            'hydrator',
            static function (array $values) use ($entityHydrator, $fetcher) {
                return $entityHydrator->hydrate($values, $fetcher);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public final function getDefinition(): Entity
    {
        return $this->definition;
    }

    /**
     * {@inheritdoc}
     */
    public final function getRelatedRepository(string $propertyOrClassName): Repository
    {
        return $this->manager->getRepository(
            $this
                ->definition
                ->getRelation($propertyOrClassName)
                ->getClassName()
        );
    }

    /**
     * Create select query builder.
     */
    protected function query(?string $primaryTableAlias = null): EntityQuery
    {
        return $this
            ->manager
            ->query(
                $this->className,
                $primaryTableAlias
            )
        ;
    }
}

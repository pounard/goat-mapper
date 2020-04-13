<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Entity;

use Goat\Mapper\Definition\GrowableIdentifierList;
use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use Goat\Mapper\Error\QueryError;
use Goat\Mapper\Hydration\EntityHydrator\EntityHydratorFactory;
use Goat\Mapper\Query\Graph\Node;
use Goat\Mapper\Query\Graph\RootNode;
use Goat\Mapper\Query\Graph\Source;
use Goat\Mapper\Query\Graph\Traverser;
use Goat\Mapper\Query\Relation\DefaultRelationFetcher;
use Goat\Mapper\Query\Relation\PreFetchRelationFetcher;
use Goat\Query\ExpressionRelation;
use Goat\Query\SelectQuery;
use Goat\Runner\ResultIterator;
use Goat\Runner\Runner;

class EntityQuery
{
    private QueryBuilderFactory $queryBuilderFactory;
    private DefinitionRegistry $definitionRegistry;
    private EntityHydratorFactory $entityHydratorFactory;
    private Runner $runner;

    private bool $locked = false;
    private RootNode $rootNode;
    private ?SelectQuery $query = null;

    /** @var array<string,string> */
    private array $aliases = [];
    /** @var array<string,string> */
    private array $aliasPathMap = [];
    /** @var array<string,bool> */
    private array $circularReferenceBreaker = [];

    public function __construct(
        QueryBuilderFactory $queryBuilderFactory,
        DefinitionRegistry $definitionRegistry,
        EntityHydratorFactory $entityHydratorFactory,
        Runner $runner,
        string $className,
        ?string $primaryTableAlias = null
    ) {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->definitionRegistry = $definitionRegistry;
        $this->entityHydratorFactory = $entityHydratorFactory;

        $this->runner = $runner;
        $this->rootNode = new RootNode($className);

        $definition = $definitionRegistry->getDefinition($className);
        $this->rootNode->setAlias($this->getNextAlias($primaryTableAlias ?? $definition->getTable()->getName()));
        $this->rootNode->toggleLoad();
    }

    public function getRootNode(): RootNode
    {
        return $this->rootNode;
    }

    /**
     * Get definition registry
     */
    public function getDefinitionRegistry(): DefinitionRegistry
    {
        return $this->definitionRegistry;
    }

    /**
     * Get the select query
     */
    public function getQuery(): SelectQuery
    {
        return $this->query ?? (
            $this->query = $this->createQuery()
        );
    }

    /**
     * Load from a source entity from a relation.
     *
     * @param \Goat\Mapper\Definition\Identifier[] $identifiers
     *   Relation source identifier(s).
     */
    public function from(string $className, string $propertyName, iterable $identifiers): self
    {
        $this->ensureQueryIsNotLocked();

        $this->rootNode->withSource(
            new Source($className, $propertyName, $identifiers)
        );

        return $this;
    }

    /**
     * Trigger eager loading of a nested property.
     *
     * This can recurse indefinitely over the repository dependency graph
     * so the path can basically be something like:
     *
     *    'client.address.street'
     * 
     * for exemple, each dot-separated word must be a property name or a raw
     * column name of its parent.
     *
     * Every found relation entity will be eagerly loaded, the circuit will
     * silently break on first to-many relation found.
     */
    public function eager(string $propertyPath): self
    {
        $this->ensureQueryIsNotLocked();

        $this->doAddRecursion(
            $this->rootNode,
            \explode('.', $propertyPath)
        );

        return $this;
    }

    /**
     * Make loaded entities match a condition of any nested property.
     *
     * This can recurse indefinitely over the repository dependency graph
     * so the path can basically be something like:
     *
     *    'client.address.street.number'
     * 
     * for exemple, each dot-separated word must be a property name or a raw
     * column name of its parent.
     *
     * In case of a to-many relation found, query will start adding CTE's
     * and use EXISTS statements. Beware that the generated query might be
     * very, very slow.
     */
    public function matches(string $propertyPath, $expression): self
    {
        $this->ensureQueryIsNotLocked();

        if (false === \strpos($propertyPath, '.')) {
            $this->rootNode->withCondition($propertyPath, $expression);
            $this->rootNode->toggleMatch(true);

            return $this;
        }

        $this->doMatchRecursion(
            $this->rootNode,
            \explode('.', $propertyPath),
            $expression
        );

        return $this;
    }

    /**
     * Compute a non duplicated alias for given table name or alias.
     */
    public function getNextAlias(string $alias): string
    {
        $this->ensureQueryIsNotLocked();

        if (isset($this->aliases[$alias])) {
            return $alias.'_'.(++$this->aliases[$alias]);
        }
        $this->aliases[$alias] = 0;

        return $alias;
    }

    private function doAddRecursion(Node $node, array $path): void
    {
        $propertyName = \array_shift($path);

        $entity = $this->doFindTargetEntity($node, $propertyName);
        if (!$entity) {
            return;
        }

        $child = $node->upsert($propertyName, $entity->getClassName());
        $child->toggleLoad(true);
        // @todo This will be called more than once.
        $child->setAlias($this->getNextAlias($entity->getTable()->getName()));

        if ($path) {
            $this->doAddRecursion($child, $path);
        }
    }

    private function doMatchRecursion(Node $node, array $path, $expression): void
    {
        $propertyName = \array_shift($path);

        $entity = $this->doFindTargetEntity($node, $propertyName);
        if (!$entity) {
            return;
        }

        $child = $node->upsert($propertyName, $entity->getClassName());
        $child->toggleMatch(true);
        // @todo This will be called more than once.
        $child->setAlias($this->getNextAlias($entity->getTable()->getName()));

        if ($path) {
            $this->doMatchRecursion($child, $path, $expression);
        } else {
            $child->withCondition($propertyName, $expression);
        }
    }

    private function doFindTargetEntity(Node $node, string $propertyName): ?Entity
    {
        $parentClassName = $node->getClassName();

        $relation = $this
            ->definitionRegistry
            ->getDefinition($parentClassName)
            ->getRelation($propertyName)
        ;

        // Relation could be a class name.
        $propertyName = $relation->getName();

        // Break eager loading for to-many relations, using a prefetcher
        // will do the job otherwise. The N+1 problem stops where N = 1 with
        // to many relations, problem is much more complex for those.
        if ($relation->isMultiple()) {
            // @todo when instrumentation will be implemented, log here.
            return null;
        }

        if ($this->isCircularDependency($parentClassName, $propertyName)) {
            throw new QueryError(\sprintf(
                "Circular dependency requested for relation '%s' of class %s",
                $propertyName,
                $parentClassName
            ));
        }

        return $relation->getEntity();
    }

    /**
     * Fetch the build select query, you can then call execute() to fetch data.
     */
    public function build(): SelectQuery
    {
        $this->locked = true;

        $traverser = Traverser::createQueryBuilder();
        $traverser->traverse($this);

        $query = $this->getQuery();
        $className = $this->rootNode->getClassName();
        $primaryKey = $this->definitionRegistry->getDefinition($className)->getPrimaryKey();

        // @todo make this conditionnal.
        // Create an identifier list, those identifiers will be used for
        // prefetching N+1 lazy properties. It will be populated during entity
        // hydration first iteration, then the pre-fetcher will be able to use
        // this list to proceed to bulk load.
        $identifiers = new GrowableIdentifierList();

        // Fetch that is aware of identifier list to pre-fetch.
        $fetcher = new PreFetchRelationFetcher(
            new DefaultRelationFetcher(
                $this->queryBuilderFactory
            ),
            $identifiers
        );

        $entityHydrator = $this->entityHydratorFactory->createHydrator($className);

        $query->setOption(
            'result_decorator',
            static function (ResultIterator $result) use ($identifiers): ResultIterator {
                // Force iterator to iterate at least once, otherwise the
                // pre-fetch iterator list cannot be complete.
                $result->setRewindable(true);
                foreach ($result as $_) {
                    // Do nothing, just iterate.
                }

                $identifiers->lock();

                // @todo this method is not public.
                $result->rewind();

                return $result;
            }
        );

        $query->setOption(
            'hydrator',
            static function (array $values) use ($entityHydrator, $fetcher, $primaryKey, $identifiers) {
                // Populate pre-fetch identifier list from raw entity values
                // before they have been hydrated (otherwise we cannot guess
                // values).
                // During this workflow, we cannot expect the iterator to have
                // been fully iterated prior to fetch relations, but we hope.
                $identifiers->add($primaryKey->createIdentifierFromRow($values));

                return $entityHydrator->hydrate($values, $fetcher);
            }
        );

        return $query;
    }

    /**
     * Alias of calling self::build()->execute().
     */
    public function execute(): ResultIterator
    {
        return $this->build()->execute();
    }

    /**
     * Create select query
     */
    private function createQuery(): SelectQuery
    {
        $table = $this
            ->definitionRegistry
            ->getDefinition(
                $this->rootNode->getClassName()
            )
            ->getTable()
        ;

        $relation = ExpressionRelation::create(
            $table->getName(),
            $this->rootNode->getAlias(),
            $table->getSchema()
        );

        return $this
            ->runner
            ->getQueryBuilder()
            ->select($relation)
        ;
    }

    /**
     * Check for circular dependency.
     */
    private function isCircularDependency(string $className, string $propertyName): bool
    {
        return isset($this->circularReferenceBreaker[$className][$propertyName]);
    }

    /**
     * Raise error if query is locked.
     */
    private function ensureQueryIsNotLocked(): void
    {
        if ($this->locked) {
            throw new QueryError("Query is locked.");
        }
    }
}

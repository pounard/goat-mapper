<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Entity;

use Goat\Mapper\Definition\GrowableIdentifierList;
use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use Goat\Mapper\Error\QueryError;
use Goat\Mapper\Hydration\EntityHydrator\EntityHydratorFactory;
use Goat\Mapper\Query\Graph\Node;
use Goat\Mapper\Query\Graph\PropertyNode;
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

        // Set-up initial relation state: mark all relations that defaults to
        // being lazy loaded to be lazy loaded indeed.
        // @todo This is not ready, because when fetching related entities we
        //   already have loaded the parent, case in which we probably MUST NOT
        //   eager JOIN them, but let it lazy. This is true when relation is
        //   represented both ways in your definitions.
        /*
        foreach ($definition->getRelations() as $relation) {
            \assert($relation instanceof Relation);
            if ($relation->doEagerLoad()) {
                $this->doAddRecursion($this->rootNode, [$relation->getName()], false);
            }
        }
         */
    }

    /**
     * Get root node.
     */
    public function getRootNode(): RootNode
    {
        return $this->rootNode;
    }

    /**
     * Get definition registry.
     */
    public function getDefinitionRegistry(): DefinitionRegistry
    {
        return $this->definitionRegistry;
    }

    /**
     * Get the select query.
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

        $this->doAddRecursion($this->rootNode, \explode('.', $propertyPath), false);

        return $this;
    }

    /**
     * Trigger lazy loading of a nested property.
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
    public function lazy(string $propertyPath): self
    {
        $this->ensureQueryIsNotLocked();

        $this->doAddRecursion($this->rootNode, \explode('.', $propertyPath), true);

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

        $this->doMatchRecursion($this->rootNode, \explode('.', $propertyPath), $expression);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNextAlias(string $alias): string
    {
        if (isset($this->aliases[$alias])) {
            return $alias.'_'.(++$this->aliases[$alias]);
        }
        $this->aliases[$alias] = 0;

        return $alias;
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

    private function doUpsertChild(Node $node, string $propertyName): PropertyNode
    {
        $parentClassName = $node->getClassName();

        $relation = $this
            ->definitionRegistry
            ->getDefinition($parentClassName)
            ->getRelation($propertyName)
        ;

        // Relation could be a class name.
        $propertyName = $relation->getName();

        if ($this->isCircularDependency($parentClassName, $propertyName)) {
            throw new QueryError(\sprintf(
                "Circular dependency requested for relation '%s' of class %s",
                $propertyName,
                $parentClassName
            ));
        }

        $entity = $relation->getEntity();

        $child = $node->upsert($propertyName, $entity->getClassName());
        $child->setAlias($this->getNextAlias($entity->getTable()->getName()));

        return $child;
    }

    private function doAddRecursion(Node $node, array $path, bool $lazy): void
    {
        $propertyName = \array_shift($path);
        $child = $this->doUpsertChild($node, $propertyName);

        $child->toggleLoad(true);
        $child->toggleLazy(false);

        if ($path) {
            $this->doAddRecursion($child, $path, $lazy);
        }
    }

    private function doMatchRecursion(Node $node, array $path, $expression): void
    {
        $propertyName = \array_shift($path);
        $child = $this->doUpsertChild($node, $path, $expression);

        if ($path) {
            $child->toggleAnyChildShouldMatch(true);
            $this->doMatchRecursion($child, $path, $expression);
        } else {
            $child->toggleMatch(true);
            $child->withCondition($propertyName, $expression);
        }
    }

    /**
     * Create select query.
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

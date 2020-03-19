<?php

declare(strict_types=1);

namespace Goat\Mapper;

use Goat\Mapper\Definition\DefinitionRegistry;
use Goat\Mapper\Error\RepositoryDoesNotExistError;
use Goat\Mapper\Hydration\EntityHydrator\EntityHydratorFactory;
use Goat\Mapper\Query\EntityQueryBuilder;
use Goat\Runner\Runner;

class DefaultRepositoryManager implements RepositoryManager
{
    /** @var Runner */
    private $runner;

    /** @var DefinitionRegistry */
    private $definitionRegistry;

    /** @var Repository[] */
    private $repositories = [];

    /** @var EntityHydratorFactory */
    private $entityHydratorFactory;

    public function __construct(
        Runner $runner,
        DefinitionRegistry $definitionRegistry,
        EntityHydratorFactory $entityHydratorFactor
    ) {
        $this->definitionRegistry = $definitionRegistry;
        $this->entityHydratorFactory = $entityHydratorFactor;
        $this->runner = $runner;
    }

    /**
     * {@inheritdoc}
     */
    public function getRunner(): Runner
    {
        return $this->runner;
    }

    /**
     * Fetch repository for class.
     *
     * @param string $className
     *   Target class name or repository alias, identifier or class name.
     *
     * @throws RepositoryDoesNotExistError
     *   If repository does not exists or if its definition is invalid.
     */
    public function getRepository(string $className): Repository
    {
        return $this->repositories[$className] ?? (
            $this->repositories[$className] = $this->createRepository($className)
        );
    }

    /**
     * Create an arbitrary entity query builder.
     */
    public function createEntityQueryBuilder(string $className): EntityQueryBuilder
    {
        return new EntityQueryBuilder(
            $this->getRepository($className),
            $this->entityHydratorFactory
        );
    }

    private function createRepository(string $className): Repository
    {
        // @todo Make it pluggage for custom implementation.
        return new DefaultRepository(
            $this->definitionRegistry->getRepositoryDefinition($className),
            $this
        );
    }
}

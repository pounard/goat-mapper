<?php

declare(strict_types=1);

namespace Goat\Mapper\Repository;

use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use Goat\Mapper\Hydration\EntityHydrator\EntityHydratorFactory;
use Goat\Mapper\Query\Entity\QueryBuilderFactory;
use Goat\Runner\Runner;

class DefaultRepositoryManager implements RepositoryManager
{
    private Runner $runner;
    private DefinitionRegistry $definitionRegistry;
    private array $repositories = [];
    private EntityHydratorFactory $entityHydratorFactory;
    private ?QueryBuilderFactory $queryBuilderFactory;

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
     * {@inheritdoc}
     */
    public function getRepository(string $className): Repository
    {
        return $this->repositories[$className] ?? (
            $this->repositories[$className] = $this->createRepository($className)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinitionRegistry(): DefinitionRegistry
    {
        return $this->definitionRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryBuilderFactory(): QueryBuilderFactory
    {
        return $this->queryBuilderFactory ?? (
            $this->queryBuilderFactory = $this->createQueryBuilderFactory()
        );
    }

    private function createRepository(string $className): Repository
    {
        // @todo Make it pluggage for custom implementation.
        return new DefaultRepository(
            $this->definitionRegistry->getDefinition($className),
            $this
        );
    }

    private function createQueryBuilderFactory(): QueryBuilderFactory
    {
        return new QueryBuilderFactory(
            $this->runner,
            $this->definitionRegistry,
            $this->entityHydratorFactory
        );
    }
}

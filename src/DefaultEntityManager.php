<?php

declare(strict_types=1);

namespace Goat\Mapper;

use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use Goat\Mapper\Hydration\EntityHydrator\EntityHydratorFactory;
use Goat\Mapper\Query\Entity\EntityQuery;
use Goat\Mapper\Query\Entity\QueryBuilderFactory;
use Goat\Mapper\Repository\Repository;
use Goat\Mapper\Repository\Factory\DefaultRepositoryFactory;
use Goat\Mapper\Repository\Registry\DefaultRepositoryRegistry;
use Goat\Mapper\Repository\Registry\RepositoryRegistry;
use Goat\Runner\Runner;

class DefaultEntityManager implements EntityManager
{
    private Runner $runner;
    private DefinitionRegistry $definitionRegistry;
    private EntityHydratorFactory $entityHydratorFactory;
    private RepositoryRegistry $repositoryRegistry;
    private ?QueryBuilderFactory $queryBuilderFactory;

    public function __construct(
        Runner $runner,
        DefinitionRegistry $definitionRegistry,
        EntityHydratorFactory $entityHydratorFactor,
        ?RepositoryRegistry $repositoryRegistry = null
    ) {
        $this->definitionRegistry = $definitionRegistry;
        $this->entityHydratorFactory = $entityHydratorFactor;
        $this->repositoryRegistry = $repositoryRegistry ?? $this->createDefaultRepositoryRegistry();
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

    /**
     * {@inheritdoc}
     */
    public function getRepository(string $className): Repository
    {
        return $this->repositoryRegistry->getRepository($className);
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $className, ?string $primaryTableAlias = null): EntityQuery
    {
        return $this
            ->getQueryBuilderFactory()
            ->query(
                $className,
                $primaryTableAlias
            )
        ;
    }

    private function createDefaultRepositoryRegistry(): RepositoryRegistry
    {
        $repositoryFactory = new DefaultRepositoryFactory();
        $repositoryFactory->setEntityManager($this);

        return new DefaultRepositoryRegistry($repositoryFactory);
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

<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Unit;

use Goat\Mapper\DefaultEntityManager;
use Goat\Mapper\Repository\DefaultRepository;
use Goat\Mapper\Repository\Repository;
use Goat\Mapper\Repository\Registry\RepositoryRegistry;
use Goat\Mapper\Tests\AbstractRepositoryTest;
use Goat\Mapper\Tests\Mock\WithManyToOneRelation;
use Goat\Mapper\Tests\Mock\WithOneToManyRelation;
use Goat\Runner\Testing\NullRunner;

final class DefaultEntityManagerTest extends AbstractRepositoryTest
{
    public function testTrivialGet(): void
    {
        $runner = new NullRunner();
        $definitionRegistry = $this->createDefinitionRegistry();
        $entityHydratorFactory = $this->createEntityHydratorFactory();

        $manager = new DefaultEntityManager(
            $runner,
            $definitionRegistry,
            $entityHydratorFactory
        );

        self::assertSame($runner, $manager->getRunner());
        self::assertSame($definitionRegistry, $manager->getDefinitionRegistry());
    }

    public function testGetQueryBuilderFactory(): void
    {
        $manager = new DefaultEntityManager(
            new NullRunner(),
            $this->createDefinitionRegistry(),
            $this->createEntityHydratorFactory()
        );

        $queryBuilderFactory = $manager->getQueryBuilderFactory();

        // Instance is created only once.
        self::assertSame($queryBuilderFactory, $manager->getQueryBuilderFactory());
    }

    public function testQuery(): void
    {
        $manager = new DefaultEntityManager(
            new NullRunner(),
            $this->createDefinitionRegistry(),
            $this->createEntityHydratorFactory()
        );

        $entityQuery = $manager->query(WithManyToOneRelation::class, 'foo');

        self::assertSame(
            WithManyToOneRelation::class,
            $entityQuery->getRootNode()->getClassName()
        );
        self::assertSame(
            'foo',
            $entityQuery->getRootNode()->getAlias()
        );
    }

    public function testGetRepositoryWithDefaultCreatedInstance(): void
    {
        $manager = new DefaultEntityManager(
            new NullRunner(),
            $this->createDefinitionRegistry(),
            $this->createEntityHydratorFactory()
        );

        self::assertInstanceOf(
            DefaultRepository::class,
            $manager->getRepository(WithOneToManyRelation::class)
        );
    }

    public function testGetRepositoryWithInjectedInstance(): void
    {
        $manager = new DefaultEntityManager(
            new NullRunner(),
            $this->createDefinitionRegistry(),
            $this->createEntityHydratorFactory(),
            new class () implements RepositoryRegistry {
                public function getRepository(string $className): Repository
                {
                    throw new \DomainException("Pouet, tout simplement.");
                }
            }
        );

        self::expectExceptionMessage("Pouet, tout simplement.");

        $manager->getRepository(WithOneToManyRelation::class);
    }
}

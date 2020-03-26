<?php

declare(strict_types=1);

namespace Goat\Mapper\Test\Hydration\EntityHydrator;

use Goat\Mapper\Definition\Identifier;
use Goat\Mapper\Hydration\Collection\Collection;
use Goat\Mapper\Hydration\Collection\EmptyCollection;
use Goat\Mapper\Hydration\EntityHydrator\EntityHydratorContext;
use Goat\Mapper\Query\Relation\RelationFetcher;
use Goat\Mapper\Query\Relation\ResultSet;
use Goat\Mapper\Repository\Repository;
use Goat\Mapper\Tests\AbstractRepositoryTest;
use Goat\Mapper\Tests\Mock\WithToManyInTargetRelation;
use Goat\Mapper\Tests\Mock\WithToOneInSourceRelation;
use Goat\Mapper\Tests\Mock\WithToOneInTargetRelation;
use Goat\Mapper\Tests\Mock\WithoutRelation;
use Ramsey\Uuid\Uuid;

final class EntityHydratorFactoryTest extends AbstractRepositoryTest
{
    private static function callEntityHydrator(Repository $repository, callable $callback, array $values): object
    {
        return $callback(
            $repository
                ->getRepositoryDefinition()
                ->getPrimaryKey()
                ->createIdentifierFromRow($values)
            ,
            $values
        );
    }

    private function createContext(string $className): EntityHydratorContext
    {
        $context = new EntityHydratorContext(
            $className,
        );

        $context->relationFetcher = new class () implements RelationFetcher {
            public function single(string $className, string $propertyName, Identifier $id): ?object
            {
                return null;
            }

            public function collection(string $className, string $propertyName, Identifier $id): Collection
            {
                return new EmptyCollection();
            }

            public function bulkSingle(string $className, string $propertyName, array $identifiers): ResultSet
            {
                throw new \BadMethodCallException();
            }

            public function bulkCollection(string $className, string $propertyName, array $identifiers): ResultSet
            {
                throw new \BadMethodCallException();
            }
        };

        return $context;
    }

    public function testCreateHydratorWithoutRelations(): void
    {
        $instance = $this->createEntityHydratorFactory();
        $repository = $this->createRepositoryManager()->getRepository(WithoutRelation::class);

        $context = $this->createContext(WithoutRelation::class);

        $callable = $instance->createHydrator($context);

        $object = self::callEntityHydrator($repository, $callable, [
            'id' => $reference = Uuid::uuid4(),
            'value' => 'foo',
        ]);

        self::assertInstanceOf(WithoutRelation::class, $object);
        self::assertTrue($reference->equals($object->getId()));
        self::assertSame('foo', $object->getValue());
    }

    public function testCreateHydratorWithToOneInSourceRelation(): void
    {
        $instance = $this->createEntityHydratorFactory();
        $repository = $this->createRepositoryManager()->getRepository(WithToOneInSourceRelation::class);

        $context = $this->createContext(WithToOneInSourceRelation::class);
        $context->lazyPropertyNames[] = 'relatedEntity';

        $callable = $instance->createHydrator($context);

        $object = self::callEntityHydrator($repository, $callable, [
            'id' => $reference = Uuid::uuid4(),
            'relatedEntity' => new Identifier([
                Uuid::uuid4(),
            ]),
        ]);

        self::assertInstanceOf(WithToOneInSourceRelation::class, $object);
        self::assertTrue($reference->equals($object->getId()));

        $related = $object->getRelatedEntity();
        self::assertInstanceOf(WithToOneInTargetRelation::class, $related);

        // @todo Instance does not exists in database, it should return null.
        // self::expectException(EntityDoesNotExistError::class);
        // $related->getRelatedEntity();
    }

    public function testCreateHydratorWithToOneInSourceRelationNullValue(): void
    {
        $instance = $this->createEntityHydratorFactory();
        $repository = $this->createRepositoryManager()->getRepository(WithToOneInSourceRelation::class);

        $context = $this->createContext(WithToOneInSourceRelation::class);
        $context->lazyPropertyNames[] = 'relatedEntity';

        $callable = $instance->createHydrator($context);

        $object = self::callEntityHydrator($repository, $callable, [
            'id' => $reference = Uuid::uuid4(),
            'relatedEntity' => null,
        ]);

        self::assertInstanceOf(WithToOneInSourceRelation::class, $object);
        self::assertTrue($reference->equals($object->getId()));

        // @todo Proxy is wrong.
        // $related = $object->getRelatedEntity();
        // self::assertNull($related);
    }

    public function testCreateHydratorWithToManyInTargetRelationWithIdentifierList(): void
    {
        $instance = $this->createEntityHydratorFactory();
        $repository = $this->createRepositoryManager()->getRepository(WithToManyInTargetRelation::class);

        $context = $this->createContext(WithToManyInTargetRelation::class);
        $context->lazyPropertyNames[] = 'relatedCollection';

        $callable = $instance->createHydrator($context);

        // @todo temporary.
        //self::expectExceptionMessage("Not implemented yet.");
        $object = self::callEntityHydrator($repository, $callable, [
            'id' => $reference = Uuid::uuid4(),
            'relatedCollection' => [
                new Identifier([
                    Uuid::uuid4(),
                ]),
                new Identifier([
                    Uuid::uuid4(),
                ]),
            ],
        ]);

        self::assertInstanceOf(WithToManyInTargetRelation::class, $object);
        self::assertTrue($reference->equals($object->getId()));
    }
}

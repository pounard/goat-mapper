<?php

declare(strict_types=1);

namespace Goat\Mapper\Test\Hydration;

use Goat\Mapper\Definition\Identifier;
use Goat\Mapper\Error\EntityDoesNotExistError;
use Goat\Mapper\Tests\RepositoryTestTrait;
use Goat\Mapper\Tests\Mock\WithToManyInTargetRelation;
use Goat\Mapper\Tests\Mock\WithToOneInSourceRelation;
use Goat\Mapper\Tests\Mock\WithoutRelation;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class EntityHydratorFactoryTest extends TestCase
{
    use RepositoryTestTrait;

    public function testCreateHydratorWithoutRelations(): void
    {
        $instance = $this->createEntityHydratorFactory();
        $repository = $this->createRepositoryManager()->getRepository(WithoutRelation::class);

        $callable = $instance->createHydrator(
            $repository,
            []
        );

        $object = $callable([
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

        $callable = $instance->createHydrator(
            $repository,
            [
                'relatedEntity',
            ]
        );

        $object = $callable([
            'id' => $reference = Uuid::uuid4(),
            'relatedEntity' => new Identifier([
                Uuid::uuid4(),
            ]),
        ]);

        self::assertInstanceOf(WithToOneInSourceRelation::class, $object);
        self::assertTrue($reference->equals($object->getId()));

        $related = $object->getRelatedEntity();
        self::assertInstanceOf(WithoutRelation::class, $related);

        // Instance does not exists in database.
        self::expectException(EntityDoesNotExistError::class);
        $related->getValue();
    }

    public function testCreateHydratorWithToOneInSourceRelationNullValue(): void
    {
        $instance = $this->createEntityHydratorFactory();
        $repository = $this->createRepositoryManager()->getRepository(WithToOneInSourceRelation::class);

        $callable = $instance->createHydrator(
            $repository,
            [
                'relatedEntity',
            ]
        );

        $object = $callable([
            'id' => $reference = Uuid::uuid4(),
            'relatedEntity' => null,
        ]);

        self::assertInstanceOf(WithToOneInSourceRelation::class, $object);
        self::assertTrue($reference->equals($object->getId()));

        $related = $object->getRelatedEntity();
        self::assertNull($related);
    }

    public function testCreateHydratorWithToManyInTargetRelationWithIdentifierList(): void
    {
        $instance = $this->createEntityHydratorFactory();
        $repository = $this->createRepositoryManager()->getRepository(WithToManyInTargetRelation::class);

        $callable = $instance->createHydrator(
            $repository,
            [
                'relatedCollection',
            ]
        );

        // @todo temporary.
        self::expectExceptionMessage("Not implemented yet.");
        $object = $callable([
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

<?php

declare(strict_types=1);

namespace Goat\Mapper\Test\Unit\Query\Relation;

use Goat\Mapper\Definition\Identifier;
use Goat\Mapper\Error\QueryError;
use Goat\Mapper\Tests\AbstractRepositoryTest;
use Goat\Mapper\Tests\Mock\WithManyToOneRelation;
use Goat\Mapper\Tests\Mock\WithOneToManyRelation;

final class RelationQueryBuilderTest extends AbstractRepositoryTest
{
    public function testKeyWithIdentifierMismatchRaiseError(): void
    {
        $manager = $this->createRepositoryManager();
        $query = $manager
            ->getQueryBuilderFactory()
            ->related(
                WithManyToOneRelation::class,
                'relatedEntity',
                [new Identifier(['foo', 2])]
            )
        ;

        self::expectException(QueryError::class);
        self::expectExceptionMessageRegExp('/^Identifier.*not compatible.*/');
        $query->build();
    }

    public function testSingleColumnWithKeyInSource(): void
    {
        $manager = $this->createRepositoryManager();
        $query = $manager
            ->getQueryBuilderFactory()
            ->related(
                WithManyToOneRelation::class,
                'relatedEntity',
                [
                    new Identifier([1]),
                    new Identifier([2]),
                    new Identifier([3])
                ]
            )
        ;

        self::assertSameSql(<<<SQL
SELECT
    "with_one_to_many"."id"
        AS "id",
    "with_one_to_many"."value"
        AS "value"
FROM "with_one_to_many"
INNER JOIN "with_many_to_one"
    ON (
        "with_one_to_many"."id" = "with_many_to_one"."related_entity_id"
    )
WHERE (
    "with_many_to_one"."related_entity_id" IN (?, ?, ?)
)
SQL
            , $query->build()
        );
    }

    public function testSingleColumnWithKeyInTarget(): void
    {
        $manager = $this->createRepositoryManager();
        $query = $manager
            ->getQueryBuilderFactory()
            ->related(
                WithOneToManyRelation::class,
                'relatedCollection',
                [
                    new Identifier([1]),
                    new Identifier([2]),
                    new Identifier([3])
                ]
            )
        ;

        self::assertSameSql(<<<SQL
SELECT
    "with_many_to_one"."id"
        AS "id",
    "with_many_to_one"."related_entity_id"
        AS "relatedEntityId"
FROM "with_many_to_one"
WHERE (
    "with_many_to_one"."related_entity_id" IN (?, ?, ?)
)
SQL
            , $query->build()
        );
    }

    public function testMultipleColumnWithKeyInTarget(): void
    {
        self::markTestSkipped("Needs an entity for this.");

        $manager = $this->createRepositoryManager();
        $query = $manager
            ->getQueryBuilderFactory()
            ->related(
                WithManyToOneRelation::class,
                'relatedEntity',
                [
                    new Identifier([1]),
                    new Identifier([2]),
                    new Identifier([3])
                ]
            )
        ;

        self::assertSameSql(<<<SQL
WRITE ME 1
SQL
            , $query->build()
        );
    }

    public function testMultipleColumnWithKeyInSource(): void
    {
        self::markTestSkipped("Needs an entity for this.");

        $manager = $this->createRepositoryManager();
        $query = $manager
            ->getQueryBuilderFactory()
            ->related(
                WithManyToOneRelation::class,
                'relatedEntity',
                [
                    new Identifier([1]),
                    new Identifier([2]),
                    new Identifier([3])
                ]
            )
        ;

        self::assertSameSql(<<<SQL
WRITE ME 2
SQL
            , $query->build()
        );
    }
}

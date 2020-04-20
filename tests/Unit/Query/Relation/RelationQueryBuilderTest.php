<?php

declare(strict_types=1);

namespace Goat\Mapper\Test\Unit\Query\Relation;

use Goat\Mapper\Definition\Identifier;
use Goat\Mapper\Error\QueryError;
use Goat\Mapper\Tests\AbstractRepositoryTest;
use Goat\Mapper\Tests\Mock\WithManyToManyBarRelation;
use Goat\Mapper\Tests\Mock\WithManyToManyFooRelation;
use Goat\Mapper\Tests\Mock\WithManyToOneRelation;
use Goat\Mapper\Tests\Mock\WithOneToManyRelation;

final class RelationQueryBuilderTest extends AbstractRepositoryTest
{
    public function testKeyWithIdentifierMismatchRaiseError(): void
    {
        $manager = $this->createEntityManager();
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

    public function testAnyToOne(): void
    {
        $manager = $this->createEntityManager();
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
WHERE EXISTS (
    SELECT 1
    FROM "with_many_to_one"
    WHERE (
        "with_many_to_one"."id" IN (?,?,?)
    )
    AND (
        "with_one_to_many"."id" = "with_many_to_one"."related_entity_id"
    )
)
SQL
            , $query->build()
        );
    }

    public function testOneToMany(): void
    {
        $manager = $this->createEntityManager();
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
        AS "relatedEntityId",
    "with_many_to_one"."related_entity_serial"
        AS "relatedEntitySerial"
FROM "with_many_to_one"
WHERE (
    "with_many_to_one"."related_entity_id" IN (?, ?, ?)
)
SQL
            , $query->build()
        );
    }

    public function testOneToManyWithSourceKeyNotPrimaryKey(): void
    {
        $manager = $this->createEntityManager();
        $query = $manager
            ->getQueryBuilderFactory()
            ->related(
                WithOneToManyRelation::class,
                'relatedCollectionUsingSerial',
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
        AS "relatedEntityId",
    "with_many_to_one"."related_entity_serial"
        AS "relatedEntitySerial"
FROM "with_many_to_one"
INNER JOIN "with_one_to_many"
    ON (
        "with_many_to_one"."related_entity_serial" = "with_one_to_many"."serial"
    )
WHERE (
    "with_one_to_many"."id" IN (?, ?, ?)
)
SQL
            , $query->build()
        );
    }

    public function testOneToManyWithMultipleColumnKey(): void
    {
        self::markTestSkipped("Needs an entity for this.");
    }

    public function testAnyToOneWithMultipleColumnKey(): void
    {
        self::markTestSkipped("Needs an entity for this.");
    }

    public function testManyToManyWithNonPrimaryKeyInTarget(): void
    {
        $manager = $this->createEntityManager();
        $query = $manager
            ->getQueryBuilderFactory()
            ->related(
                WithManyToManyBarRelation::class,
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
    "with_many_to_many_foo"."id"
        AS "id",
    "with_many_to_many_foo"."serial"
        AS "serial"
FROM "with_many_to_many_foo"
WHERE EXISTS (
    SELECT 1
    FROM "bar_to_foo"
    WHERE (
        "bar_to_foo"."foo_id" = "with_many_to_many_foo"."serial"
    )
    AND (
        "bar_to_foo"."bar_id" IN (?,?,?)
    )
)
SQL
            , $query->build()
        );
    }

    public function testManyToManyWithNonPrimaryKeyInSource(): void
    {
        $manager = $this->createEntityManager();
        $query = $manager
            ->getQueryBuilderFactory()
            ->related(
                WithManyToManyFooRelation::class,
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
    "with_many_to_many_bar"."id"
        AS "id"
FROM "with_many_to_many_bar"
WHERE EXISTS (
    SELECT 1
    FROM "bar_to_foo"
    INNER JOIN "with_many_to_many_foo"
        ON (
            "bar_to_foo"."foo_id" = "with_many_to_many_foo"."serial"
        )
    WHERE (
        "bar_to_foo"."bar_id" = "with_many_to_many_bar"."id"
    )
)
SQL
            , $query->build()
        );
    }
}

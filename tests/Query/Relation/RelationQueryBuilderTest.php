<?php

declare(strict_types=1);

namespace Goat\Mapper\Test\Query\Relation;

use Goat\Mapper\Definition\Identifier;
use Goat\Mapper\Error\QueryError;
use Goat\Mapper\Tests\AbstractRepositoryTest;
use Goat\Mapper\Tests\Mock\WithToOneInSourceRelation;
use Goat\Mapper\Tests\Mock\WithToOneInTargetRelation;

final class RelationQueryBuilderTest extends AbstractRepositoryTest
{
    public function testKeyWithIdentifierMismatchRaiseError(): void
    {
        $manager = $this->createRepositoryManager();
        $builder = $manager->getQueryBuilderFactory()->relation();

        self::expectException(QueryError::class);
        self::expectExceptionMessageRegExp('/^Identifier.*not compatible.*/');

        $builder->createFetchRelatedQuery(
            WithToOneInTargetRelation::class,
            'relatedEntity',
            [
                new Identifier(['foo', 2]),
            ]
        );
    }

    public function testSingleColumnWithKeyInTarget(): void
    {
        $manager = $this->createRepositoryManager();
        $builder = $manager->getQueryBuilderFactory()->relation();

        $queryBuilder = $builder->createFetchRelatedQuery(
            WithToOneInTargetRelation::class,
            'relatedEntity',
            [
                new Identifier([1]),
                new Identifier([2]),
                new Identifier([3])
            ]
        );

        self::assertSameSql(<<<SQL
SELECT
    "to_one_in_source"."id"
        AS "id",
    "to_one_in_source"."target_id"
        AS "targetid"
FROM "public"."to_one_in_source"
WHERE (
    "to_one_in_source"."target_id" IN (?, ?, ?)
)
SQL
            , $queryBuilder->build()
        );
    }

    public function testSingleColumnWithKeyInSource(): void
    {
        $manager = $this->createRepositoryManager();
        $builder = $manager->getQueryBuilderFactory()->relation();

        $queryBuilder = $builder->createFetchRelatedQuery(
            WithToOneInSourceRelation::class,
            'relatedEntity',
            [
                new Identifier([1]),
                new Identifier([2]),
                new Identifier([3])
            ]
        );

        self::assertSameSql(<<<SQL
SELECT
    "to_one_in_target"."id"
        AS "id"
FROM "public"."to_one_in_target"
INNER JOIN "public"."to_one_in_source"
    ON (
        "to_one_in_target"."id" = "to_one_in_source"."target_id"
    )
WHERE (
    "to_one_in_target"."id" IN (?, ?, ?)
)
SQL
            , $queryBuilder->build()
        );
    }

    public function testMultipleColumnWithKeyInTarget(): void
    {
        self::markTestSkipped("Needs an entity for this.");

        $manager = $this->createRepositoryManager();
        $builder = $manager->getQueryBuilderFactory()->relation();

        $queryBuilder = $builder->createFetchRelatedQuery(
            WithToOneInTargetRelation::class,
            'relatedEntity',
            [
                new Identifier([1]),
                new Identifier([2]),
                new Identifier([3])
            ]
        );

        self::assertSameSql(<<<SQL
WRITE ME 1
SQL
            , $queryBuilder->build()
        );
    }

    public function testMultipleColumnWithKeyInSource(): void
    {
        self::markTestSkipped("Needs an entity for this.");

        $manager = $this->createRepositoryManager();
        $builder = $manager->getQueryBuilderFactory()->relation();

        $queryBuilder = $builder->createFetchRelatedQuery(
            WithToOneInSourceRelation::class,
            'relatedEntity',
            [
                new Identifier([1]),
                new Identifier([2]),
                new Identifier([3])
            ]
        );

        self::assertSameSql(<<<SQL
WRITE ME 2
SQL
            , $queryBuilder->build()
        );
    }
}

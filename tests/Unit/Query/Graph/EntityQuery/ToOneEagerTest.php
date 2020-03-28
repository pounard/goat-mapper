<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Unit\Query\Graph\EntityQuery;

use Goat\Mapper\Tests\AbstractRepositoryTest;
use Goat\Mapper\Tests\Mock\WithToOneInSourceRelation;
use Goat\Mapper\Tests\Mock\WithToOneInTargetRelation;

final class ToOneEagerTest extends AbstractRepositoryTest
{
    public function testEagerToOneInSourceTable(): void
    {
        $manager = $this->createRepositoryManager();
        $repository = $manager->getRepository(WithToOneInSourceRelation::class);

        $query = $repository
            ->query('foo')
            ->eager('relatedEntity')
            ->build()
        ;

        self::assertSameSql(<<<SQL
SELECT
    "foo"."id"
        AS "id",
    "foo"."target_id"
        AS "targetId",
    "to_one_in_target"."id"
        AS "relatedEntity.id"
FROM "public"."to_one_in_source"
    AS "foo"
LEFT OUTER JOIN "public"."to_one_in_target"
    ON (
        "to_one_in_target"."id" = "foo"."target_id"
    )
SQL,
        $query);
    }

    public function testEagerToOneInTargetTable(): void
    {
        $manager = $this->createRepositoryManager();
        $repository = $manager->getRepository(WithToOneInTargetRelation::class);

        $query = $repository
            ->query('bar')
            ->eager('relatedEntity')
            ->build()
        ;

        self::assertSameSql(<<<SQL
SELECT
    "bar"."id"
        AS "id",
    "to_one_in_source"."id"
        AS "relatedEntity.id",
    "to_one_in_source"."target_id"
        AS "relatedEntity.targetId"
FROM "public"."to_one_in_target"
    AS "bar"
LEFT OUTER JOIN "public"."to_one_in_source"
    ON (
        "to_one_in_source"."target_id" = "bar"."id"
    )
SQL,
        $query);
    }

    public function testEagerToOneInMappingTable(): void
    {
        self::markTestSkipped("Not implemented yet.");

        /*
        $manager = $this->createRepositoryManager();
        $repository = $manager->getRepository(WithToOneInMappingRelation::class);

        $query = $repository
            ->fetch('foo')
            ->eager('relatedEntity')
            ->build()
        ;

        self::assertSameSql(<<<SQL
SELECT
    "foo"."id"
        AS "id",
    "without_relation"."id"
        AS "relatedEntity.id",
    "without_relation"."value"
        AS "relatedEntity.value"
FROM "public"."with_one_to_one"
    AS "foo"
LEFT OUTER JOIN "public"."without_relation"
    ON (
        "without_relation"."id" = "foo"."id"
    )
SQL,
        $query);
         */
    }
}

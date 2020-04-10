<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Unit\Query\Entity\EntityQuery;

use Goat\Mapper\Tests\AbstractRepositoryTest;
use Goat\Mapper\Tests\Mock\WithManyToOneRelation;
use Goat\Mapper\Tests\Mock\WithOneToManyRelation;

final class AnyToOneEagerTest extends AbstractRepositoryTest
{
    public function testEagerAnyToOneUsingProperty(): void
    {
        $manager = $this->createRepositoryManager();
        $repository = $manager->getRepository(WithManyToOneRelation::class);

        $query = $repository
            ->query('foo')
            ->eager('relatedEntity')
            ->build()
        ;

        self::assertSameSql(<<<SQL
SELECT
    "foo"."id"
        AS "id",
    "foo"."related_entity_id"
        AS "relatedEntityId",
    "with_one_to_many"."id"
        AS "relatedEntity.id",
    "with_one_to_many"."value"
        AS "relatedEntity.value"
FROM "with_many_to_one"
    AS "foo"
LEFT OUTER JOIN "with_one_to_many"
    ON (
        "with_one_to_many"."id" = "foo"."related_entity_id"
    )
SQL,
        $query);
    }

    public function testEagerAnyToOneUsingClass(): void
    {
        $manager = $this->createRepositoryManager();
        $repository = $manager->getRepository(WithManyToOneRelation::class);

        $query = $repository
            ->query('foo')
            ->eager(WithOneToManyRelation::class)
            ->build()
        ;

        self::assertSameSql(<<<SQL
SELECT
    "foo"."id"
        AS "id",
    "foo"."related_entity_id"
        AS "relatedEntityId",
    "with_one_to_many"."id"
        AS "relatedEntity.id",
    "with_one_to_many"."value"
        AS "relatedEntity.value"
FROM "with_many_to_one"
    AS "foo"
LEFT OUTER JOIN "with_one_to_many"
    ON (
        "with_one_to_many"."id" = "foo"."related_entity_id"
    )
SQL,
        $query);
    }
}

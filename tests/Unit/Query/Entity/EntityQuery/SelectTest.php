<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Unit\Query\Entity\EntityQuery;

use Goat\Mapper\Tests\AbstractRepositoryTest;
use Goat\Mapper\Tests\Mock\WithoutRelation;
use Goat\Query\Expression;
use Goat\Query\ExpressionRaw;
use Goat\Query\SelectQuery;
use Ramsey\Uuid\Uuid;

final class SelectTest extends AbstractRepositoryTest
{
    public function testConditionWithExistingColumnInKey(): void
    {
        $manager = $this->createRepositoryManager();
        $repository = $manager->getRepository(WithoutRelation::class);

        $query = $repository
            ->query()
            ->matches('id', Uuid::uuid4())
            ->build()
        ;

        self::assertSameSql(<<<SQL
SELECT
    "without_relation"."id"
        AS "id",
    "without_relation"."value"
        AS "value"
FROM "without_relation"
WHERE
    "without_relation"."id" = ?
SQL,
        $query);
    }

    public function testConditionWithExistingColumn(): void
    {
        $manager = $this->createRepositoryManager();
        $repository = $manager->getRepository(WithoutRelation::class);

        $query = $repository
            ->query()
            ->matches('value', 'foo')
            ->build()
        ;

        self::assertSameSql(<<<SQL
SELECT
    "without_relation"."id"
        AS "id",
    "without_relation"."value"
        AS "value"
FROM "without_relation"
WHERE
    "without_relation"."value" = ?
SQL,
        $query);
    }

    public function testConditionWithNonExistingColumn(): void
    {
        $manager = $this->createRepositoryManager();
        $repository = $manager->getRepository(WithoutRelation::class);

        $query = $repository
            ->query()
            ->matches('some_other', 'foo')
            ->build()
        ;

        self::assertSameSql(<<<SQL
SELECT
    "without_relation"."id"
        AS "id",
    "without_relation"."value"
        AS "value"
FROM "without_relation"
WHERE
    "some_other" = ?
SQL,
        $query);
    }

    public function testConditionWithExpression(): void
    {
        self::markTestSkipped(\sprintf(
            "%s::condition() must be fixed to accept %s instances",
            SelectQuery::class,
            Expression::class
        ));

        $manager = $this->createRepositoryManager();
        $repository = $manager->getRepository(WithoutRelation::class);

        $query = $repository
            ->fetch()
            ->condition(ExpressionRaw::create("bouh is true"))
            ->build()
        ;

        self::assertSameSql(<<<SQL
SELECT
    "without_relation"."id"
        AS "id",
    "without_relation"."value"
        AS "value"
FROM "without_relation"
WHERE
    bouh is true
SQL,
        $query);
    }

    public function testWithoutRelations(): void
    {
        $manager = $this->createRepositoryManager();
        $repository = $manager->getRepository(WithoutRelation::class);

        $query = $repository
            ->query()
            ->build()
        ;

        self::assertSameSql(<<<SQL
SELECT
    "without_relation"."id"
        AS "id",
    "without_relation"."value"
        AS "value"
FROM "without_relation"
SQL,
        $query);
    }
}

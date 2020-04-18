<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Unit\Query\Entity\EntityQuery;

use Goat\Mapper\Tests\AbstractRepositoryTest;
use Goat\Mapper\Tests\Mock\WithoutRelation;
use Goat\Query\ExpressionRaw;
use Ramsey\Uuid\Uuid;

final class MatchesTest extends AbstractRepositoryTest
{
    public function testConditionWithExistingColumnInKey(): void
    {
        $manager = $this->createEntityManager();

        $query = $manager
            ->query(WithoutRelation::class)
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
        $manager = $this->createEntityManager();

        $query = $manager
            ->query(WithoutRelation::class)
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
        $manager = $this->createEntityManager();

        $query = $manager
            ->query(WithoutRelation::class)
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
        self::markTestIncomplete("matches() method signature is too strict.");

        $manager = $this->createEntityManager();

        $query =  $manager
            ->query(WithoutRelation::class)
            ->matches('bouh', ExpressionRaw::create("bouh is true"))
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
        $manager = $this->createEntityManager();

        $query = $manager
            ->query(WithoutRelation::class)
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

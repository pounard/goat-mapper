<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Unit\Definition;

use Goat\Mapper\Definition\Column;
use Goat\Mapper\Definition\Key;
use Goat\Mapper\Error\QueryError;
use PHPUnit\Framework\TestCase;

final class KeyTest extends TestCase
{
    public function testGetSet(): void
    {
        $instance = new Key([
            new Column('a', 'int'),
            new Column('b', 'string'),
        ]);

        self::assertFalse($instance->isEmpty());
        self::assertSame(2, $instance->count());
    }

    public function testCreateIdentifierFromRow(): void
    {
        $instance = new Key([
            new Column('a', 'int'),
            new Column('c', 'string'),
        ]);

        $identifier = $instance->createIdentifierFromRow([
            'a' => 12,
            'b' => 'foo',
            'c' => 'bar',
        ]);

        self::assertSame([12, 'bar'], $identifier->toArray());
    }

    public function testCreateIdentifierFromRowFailsWithMissingValues(): void
    {
        $instance = new Key([
            new Column('a', 'int'),
            new Column('c', 'string'),
        ]);

        self::expectException(QueryError::class);
        $instance->createIdentifierFromRow([
            'a' => 12,
            'b' => 'foo',
        ]);
    }

    public function testIcCompatibleWithSameKey(): void
    {
        $instance = new Key([
            new Column('a', 'int'),
            new Column('c', 'string'),
        ]);

        $other = new Key([
            new Column('a', 'int'),
            new Column('c', 'string'),
        ]);

        self::assertTrue($instance->isCompatible($other));
    }

    public function testIsCompatibleWithDifferentColumn(): void
    {
        $instance = new Key([
            new Column('a', 'uuid'),
            new Column('c', 'string'),
        ]);

        $other = new Key([
            new Column('foo', 'uuid'),
            new Column('bar', 'string'),
        ]);

        self::assertTrue($instance->isCompatible($other));
    }

    public function testNotIsCompatibleWithDifferentCount(): void
    {
        $instance = new Key([
            new Column('a', 'uuid'),
            new Column('c', 'string'),
            new Column('b', 'text'),
        ]);

        $other = new Key([
            new Column('foo', 'uuid'),
            new Column('bar', 'string'),
        ]);

        self::assertFalse($instance->isCompatible($other));
    }

    public function testNotIsComptiableWithDifferentTypes(): void
    {
        $instance = new Key([
            new Column('a', 'uuid'),
            new Column('c', 'int'),
        ]);

        $other = new Key([
            new Column('foo', 'uuid'),
            new Column('bar', 'string'),
        ]);

        self::assertFalse($instance->isCompatible($other));
    }

    public function testNotIsCompatibleWithDifferentOrder(): void
    {
        $instance = new Key([
            new Column('a', 'uuid'),
            new Column('c', 'int'),
        ]);

        $other = new Key([
            new Column('bar', 'int'),
            new Column('foo', 'uuid'),
        ]);

        self::assertFalse($instance->isCompatible($other));
    }

    public function testToString(): void
    {
        $instance = new Key([
            new Column('a', 'uuid'),
            new Column('c', 'int'),
        ]);

        self::assertSame("('a:uuid','c:int')", $instance->toString());
    }
}

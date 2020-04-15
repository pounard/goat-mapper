<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Unit\Definition;

use Goat\Mapper\Definition\Column;
use Goat\Mapper\Definition\Identifier;
use Goat\Mapper\Definition\Key;
use Goat\Mapper\Error\QueryError;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class IdentifierTest extends TestCase
{
    public function testNormalizeAcceptsSelf(): void
    {
        $one = new Identifier(['a', 'b']);
        $two = Identifier::normalize($one);

        self::assertSame($one, $two);
    }

    public function testNormalizeAcceptsArray(): void
    {
        $instance = Identifier::normalize(['a', 1]);

        self::assertSame(['a', 1], $instance->toArray());
    }

    public function testNormalizeAcceptsIterable(): void
    {
        $generator = static function () {
            yield 'a';
            yield 1;
        };
        $instance = Identifier::normalize($generator());

        self::assertSame(['a', 1], $instance->toArray());
    }

    public function testNormalizeConvertsAnythingElseToArray(): void
    {
        $instance = Identifier::normalize(17);

        self::assertSame([17], $instance->toArray());
    }

    public function testConstructDropKeys(): void
    {
        $instance = new Identifier([
            'foo' => 'bar',
            'baz' => 13,
        ]);

        self::assertSame(['bar', 13], $instance->toArray());
    }

    public function testToString(): void
    {
        $instance = new Identifier(['bar', 13]);

        self::assertSame("('bar',13)", $instance->toString());
    }

    public function testEmptyGivesEmptyHash(): void
    {
        $instance = new Identifier([]);

        self::assertSame('', $instance->getHash());
    }

    public function testGetHashWithObjects()
    {
        $reference = Uuid::fromString('174c1ad5-a81b-40df-b8e1-ff74bff3a8c7');

        $instance = new Identifier([$reference, 12, 'a']);

        self::assertSame('174c1ad5-a81b-40df-b8e1-ff74bff3a8c7\\12\\a', $instance->getHash());
    }

    public function testEquals(): void
    {
        $a = new Identifier(['a', 12]);
        $b = new Identifier(['a', 12]);

        self::assertTrue($a->equals($b));

        $a = new Identifier(['a', 12]);
        $b = new Identifier(['b', 12]);

        self::assertFalse($a->equals($b));

    }

    public function testEqualsWithDifferentLength(): void
    {
        $a = new Identifier([1, 2]);
        $b = new Identifier([1, 2, 3]);

        self::assertFalse($a->equals($b));
    }

    public function testEqualsWithObjects(): void
    {
        $a = new Identifier([Uuid::fromString('174c1ad5-a81b-40df-b8e1-ff74bff3a8c7')]);
        $b = new Identifier([Uuid::fromString('174c1ad5-a81b-40df-b8e1-ff74bff3a8c7')]);

        self::assertTrue($a->equals($b));
    }

    public function testFailIfNotCompatible(): void
    {
        $instance = new Identifier(['bar', 13]);

        $compatible = new Key([
            new Column('foo', 'string'),
            new Column('bar', 'int'),
        ]);
        self::assertTrue($instance->isCompatible($compatible));

        $notComptible = new Key([
            new Column('foo', 'string'),
        ]);
        self::assertFalse($instance->isCompatible($notComptible));

        $instance->failIfNotCompatible($compatible);

        self::expectException(QueryError::class);
        $instance->failIfNotCompatible($notComptible);
    }
}

<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Unit\Definition;

use Goat\Mapper\Definition\Identifier;
use Goat\Mapper\Definition\IdentifierList;
use PHPUnit\Framework\TestCase;

final class IdentifierListTest extends TestCase
{
    public function testIsEmpty(): void
    {
        $list = new IdentifierList([]);

        self::assertTrue($list->isEmpty());

        $list = new IdentifierList([
            new Identifier(['foo']),
        ]);

        self::assertFalse($list->isEmpty());
    }

    public function testExists(): void
    {
        $list = new IdentifierList([
            new Identifier(['foo']),
            new Identifier(['foo', 'a']),
            new Identifier(['bar', 2]),
        ]);

        self::assertTrue(
            $list->exists(
                new Identifier(['foo'])
            )
        );

        self::assertTrue(
            $list->exists(
                new Identifier(['foo', 'a'])
            )
        );

        self::assertFalse(
            $list->exists(
                new Identifier(['foo', 'b'])
            )
        );

        self::assertFalse(
            $list->exists(
                new Identifier(['bar'])
            )
        );
    }

    public function testExistsWhenEmpty(): void
    {
        $list = new IdentifierList([]);

        self::assertFalse(
            $list->exists(
                new Identifier(['foo'])
            )
        );
    }

    public function testIterationAndToArray(): void
    {
        $list = new IdentifierList([
            new Identifier(['a']),
            new Identifier(['b']),
        ]);

        $array = $list->toArray();
        $count = 0;

        foreach ($list as $index => $identifier) {
            ++$count;
            self::assertTrue(
                $identifier->equals(
                    $array[$index]
                )
            );
        }

        self::assertSame(2, $count);
    }
}

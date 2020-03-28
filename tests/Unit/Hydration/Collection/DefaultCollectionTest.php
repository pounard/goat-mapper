<?php

declare(strict_types=1);

namespace Goat\Mapper\Test\Unit\Hydration\Collection;

use PHPUnit\Framework\TestCase;
use Goat\Mapper\Hydration\Collection\DefaultCollection;
use Goat\Mapper\Hydration\Collection\CollectionInitializerResult;

final class DefaultCollectionTest extends TestCase
{
    public function testInitializerIsCalledOnlyOnce(): void
    {
        $called = 0;

        $collection = new DefaultCollection(function () use (&$called) {
            for ($i = 1; $i < 5; ++$i) {
                yield $i;
            }
            $called++;
        });

        \iterator_to_array($collection);
        \iterator_to_array($collection);

        self::assertSame(1, $called);
    }

    public function testNotCountableInitializerCanCount(): void
    {
        $collection = new DefaultCollection(function () {
            for ($i = 1; $i < 5; ++$i) {
                yield $i;
            }
        });

        self::assertSame(4, $collection->count());
    }

    public function testInitializerReturnArray(): void
    {
        $collection = new DefaultCollection(function () {
            return ['foo' => 'a', 'bar' => 'b', 'baz' => 'c'];
        });

        self::assertSame('b', $collection['bar']);
        self::assertCount(3, $collection);
        self::assertSame(
            ['foo' => 'a', 'bar' => 'b', 'baz' => 'c'],
            \iterator_to_array($collection)
        );
    }

    public function testInitializerReturnIterable(): void
    {
        $collection = new DefaultCollection(function () {
            for ($i = 1; $i < 5; ++$i) {
                yield $i;
            }
        });

        self::assertSame(
            [1, 2, 3, 4],
            \iterator_to_array($collection)
        );
    }

    public function testInitializerReturnResultWithCount(): void
    {
        $collection = new DefaultCollection(static function () {
            return new CollectionInitializerResult(
                static function () {
                    for ($i = 1; $i < 5; ++$i) {
                        yield $i;
                    }
                },
                10 // Wrong count, but should be propagated.
            );
        });

        self::assertSame(10, $collection->count());
        self::assertSame(
            [1, 2, 3, 4],
            \iterator_to_array($collection)
        );
    }

    public function testInitializerAsArrayIsCountable(): void
    {
        $collection = new DefaultCollection(
            ['foo' => 'a', 'bar' => 'b', 'baz' => 'c']
        );

        self::assertCount(3, $collection);
        self::assertSame(
            ['foo' => 'a', 'bar' => 'b', 'baz' => 'c'],
            \iterator_to_array($collection)
        );
    }
}

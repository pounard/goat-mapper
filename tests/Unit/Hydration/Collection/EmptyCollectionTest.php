<?php

declare(strict_types=1);

namespace Goat\Mapper\Test\Unit\Hydration\Collection;

use Goat\Mapper\Hydration\Collection\EmptyCollection;
use PHPUnit\Framework\TestCase;

final class EmptyCollectionTest extends TestCase
{
    public function testEverything(): void
    {
        $collection = new EmptyCollection();

        self::assertSame(0, $collection->count());
        self::assertNull($collection[0]);
        self::assertNull($collection['foo']);

        foreach ($collection as $item) {
            self::fail("Empty collection cannot yield any result.");
        }
    }
}

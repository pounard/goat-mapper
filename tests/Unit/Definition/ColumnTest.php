<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Unit\Definition;

use PHPUnit\Framework\TestCase;
use Goat\Mapper\Definition\Column;

final class ColumnTest extends TestCase
{
    public function testEverything(): void
    {
        $column = new Column('foo', 'bar');

        self::assertSame('foo', $column->getName());
        self::assertSame('bar', $column->getType());
    }

    public function testEquals(): void
    {
        $a = new Column('foo', 'bar');
        $b = new Column('foo', 'bar');

        self::assertTrue($a->equals($b));

        $a = new Column('foo', 'bar');
        $b = new Column('foo', 'foo');

        self::assertFalse($a->equals($b));

        $a = new Column('bar', 'bar');
        $b = new Column('foo', 'bar');

        self::assertFalse($a->equals($b));
    }
}

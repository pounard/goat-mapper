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
}

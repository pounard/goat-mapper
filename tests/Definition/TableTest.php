<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Definition;

use Goat\Mapper\Definition\Table;
use PHPUnit\Framework\TestCase;

final class TableTest extends TestCase
{
    public function testEverything(): void
    {
        $tableWithSchema = new Table('foo', 'bar');

        self::assertSame('foo', $tableWithSchema->getName());
        self::assertSame('bar', $tableWithSchema->getSchema());
        self::assertSame('bar.foo', $tableWithSchema->toString());

        $tableWithoutSchema = new Table('foo');

        self::assertSame('foo', $tableWithoutSchema->getName());
        self::assertNull($tableWithoutSchema->getSchema());
        self::assertSame('foo', $tableWithoutSchema->toString());
    }
}

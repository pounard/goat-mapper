<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Unit\Definition;

use Goat\Mapper\Definition\GrowableIdentifierList;
use Goat\Mapper\Definition\Identifier;
use Goat\Mapper\Error\QueryError;
use PHPUnit\Framework\TestCase;

final class GrowableIdentifierListTest extends TestCase
{
    public function testAdd(): void
    {
        $list = new GrowableIdentifierList();

        self::assertTrue($list->isEmpty());
        self::assertFalse($list->exists(new Identifier(['foo'])));

        $list->add(new Identifier(['bar']));

        self::assertFalse($list->isEmpty());
        self::assertFalse($list->exists(new Identifier(['foo'])));
        self::assertTrue($list->exists(new Identifier(['bar'])));
    }

    public function testLockedInstanceRaiseErrorWhenAdd(): void
    {
        $list = new GrowableIdentifierList();
        $list->add(new Identifier(['bar']));

        $list->lock();

        self::expectException(QueryError::class);

        $list->add(new Identifier(['foo']));
    }
}

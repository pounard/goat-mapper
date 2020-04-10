<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Unit\Definition\Graph;

use Goat\Mapper\Definition\PrimaryKey;
use Goat\Mapper\Definition\Table;
use Goat\Mapper\Definition\Graph\DefaultEntity;
use Goat\Mapper\Definition\Graph\EntityProxy;
use Goat\Mapper\Error\ConfigurationError;
use PHPUnit\Framework\TestCase;

final class EntityProxyTest extends TestCase
{
    public function testInitializerNotReturningEntityRaiseError(): void
    {
        $proxy = new EntityProxy(\DateTime::class, static function () {
            return 'foo';
        });

        self::expectExceptionMessageRegExp('/is not a .* instance/');
        self::expectException(ConfigurationError::class);
        $proxy->getTable();
    }

    public function testInitializerReturningNullRaiseError(): void
    {
        $proxy = new EntityProxy(\DateTime::class, static function () {
            return null;
        });

        self::expectExceptionMessageRegExp('/is not a .* instance/');
        self::expectException(ConfigurationError::class);
        $proxy->getPrimaryKey();
    }

    public function testInitializerReturningAnotherClassNameRaiseError(): void
    {
        $proxy = new EntityProxy(\DateTime::class, static function () {
            return new DefaultEntity(\DateTimeInterface::class, new Table('foo'), new PrimaryKey([]));
        });

        self::expectExceptionMessageRegExp('/is .* instead of/');
        self::expectException(ConfigurationError::class);
        $proxy->getRelation('foo');
    }

    public function testInitializerWithErrorRaiseErrorInSubsequentCalls(): void
    {
        $proxy = new EntityProxy(\DateTime::class, static function () {
            return null;
        });

        try {
            $proxy->getRelations('foo');
        } catch (ConfigurationError $e) {
            // Ignore it.
        }

        self::expectExceptionMessageRegExp('/Broken entity definition for/');
        self::expectException(ConfigurationError::class);
        $proxy->getProperties();
    }

    public function testProxyMethods(): void
    {
        $table = new Table('foo');
        $primaryKey = new PrimaryKey([]);

        $proxy = new EntityProxy(\DateTime::class, static function () use ($table, $primaryKey) {
            return new DefaultEntity(\DateTime::class, $table, $primaryKey);
        });

        self::assertSame($table, $proxy->getTable());
        self::assertSame($primaryKey, $proxy->getPrimaryKey());
        // @todo getRelation
        // @todo getRelations
        // @todo getProperties
        // @todo getColumnMap
        // @todo getChildren
    }
}

<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests;

use GeneratedHydrator\Bridge\Symfony\DefaultHydrator;
use Goat\Mapper\Definition\Registry\ArrayDefinitionRegistry;
use Goat\Mapper\Definition\Registry\ChainDefinitionRegistry;
use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use Goat\Mapper\Definition\Registry\StaticEntityDefinitionRegistry;
use Goat\Mapper\Hydration\EntityHydrator\EntityHydratorFactory;
use Goat\Mapper\Hydration\HydratorRegistry\GeneratedHydratorBundleHydratorRegistry;
use Goat\Mapper\Hydration\HydratorRegistry\HydratorRegistry;
use Goat\Mapper\Hydration\Proxy\ProxyFactory;
use Goat\Mapper\Repository\DefaultRepositoryManager;
use Goat\Mapper\Repository\RepositoryManager;
use Goat\Mapper\Tests\Mock\WithMultipleColumnPrimaryKey;
use Goat\Mapper\Tests\Mock\WithToManyInMappingRelation;
use Goat\Mapper\Tests\Mock\WithToManyInTargetRelation;
use Goat\Mapper\Tests\Mock\WithToOneInMappingRelation;
use Goat\Mapper\Tests\Mock\WithToOneInSourceRelation;
use Goat\Mapper\Tests\Mock\WithToOneInTargetRelation;
use Goat\Mapper\Tests\Mock\WithoutRelation;
use Goat\Query\Query;
use Goat\Runner\Runner;
use Goat\Runner\Testing\DatabaseAwareQueryTest;
use Goat\Runner\Testing\NullRunner;
use ProxyManager\Configuration as ProxyManagerConfiguration;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;

abstract class AbstractRepositoryTest extends DatabaseAwareQueryTest
{
    final protected static function assertSameSql($expected, $actual, string $message = null): void
    {
        $formatter = (new NullRunner())->getFormatter();

        if ($expected instanceof Query) {
            $expected = $formatter->format($expected);
        }
        if ($actual instanceof Query) {
            $actual = $formatter->format($actual);
        }

        if ($message) {
            self::assertSame(
                self::normalizeSqlString($expected),
                self::normalizeSqlString($actual),
                $message
            );
        } else {
            self::assertSame(
                self::normalizeSqlString($expected),
                self::normalizeSqlString($actual)
            );
        }
    }

    final public static function getTestEntityClasses(): array
    {
        // Order is important, because of key constraints.
        return [
            WithMultipleColumnPrimaryKey::class,
            WithoutRelation::class,
            WithToManyInMappingRelation::class,
            WithToManyInTargetRelation::class,
            WithToOneInMappingRelation::class,
            WithToOneInTargetRelation::class,
            WithToOneInSourceRelation::class,
        ];
    }

    private static function normalizeSqlString(string $string): string
    {
        $string = \preg_replace('@\s*(\(|\))\s*@ms', '$1', $string);
        $string = \preg_replace('@\s*,\s*@ms', ',', $string);
        $string = \preg_replace('@\s+@ms', ' ', $string);
        $string = \strtolower($string);
        $string = \trim($string);

        return $string;
    }

    final protected function createEntityHydratorFactory(): EntityHydratorFactory
    {
        return new EntityHydratorFactory(
            $this->createDefinitionRegistry(),
            $this->createHydratorRegistry(),
            $this->createProxyFactory()
        );
    }

    final protected function createRepositoryManager(?Runner $runner = null): RepositoryManager
    {
        return new DefaultRepositoryManager(
            $runner ?? new NullRunner(),
            $this->createDefinitionRegistry(),
            $this->createEntityHydratorFactory()
        );
    }

    final protected function createSchema(Runner $runner): void
    {
        $driverName = $runner->getDriverName();

        foreach (self::getTestEntityClasses() as $className) {
            $tables = \call_user_func([$className, 'toTableSchema']);

            $createTableStatement = $tables[$driverName] ?? $tables['default'];

            $runner->execute($createTableStatement);
        }
    }

    private function createHydratorRegistry(): HydratorRegistry
    {
        return new GeneratedHydratorBundleHydratorRegistry(
            new DefaultHydrator(
                \sys_get_temp_dir()
            )
        );
    }

    private function createLazyLoadingValueHolderFactory(): LazyLoadingValueHolderFactory
    {
        $configuration = new ProxyManagerConfiguration();
        $configuration->setGeneratorStrategy(
            new FileWriterGeneratorStrategy(
                new FileLocator(
                    $configuration->getProxiesTargetDir()
                )
            )
        );

        return new LazyLoadingValueHolderFactory($configuration);
    }

    private function createProxyFactory(): ProxyFactory
    {
        return new ProxyFactory(
            $this->createLazyLoadingValueHolderFactory()
        );
    }

    private function createDefinitionRegistry(): DefinitionRegistry
    {
        $userArrayData = [];
        foreach (self::getTestEntityClasses() as $className) {
            if (\method_exists($className, 'toDefinitionArray')) {
                $userArrayData[$className] = \call_user_func([$className, 'toDefinitionArray']);
            }
        }

        return new ChainDefinitionRegistry([
            new StaticEntityDefinitionRegistry(),
            new ArrayDefinitionRegistry($userArrayData),
        ]);
    }
}

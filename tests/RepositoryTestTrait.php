<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests;

use GeneratedHydrator\Bridge\Symfony\DefaultHydrator;
use Goat\Mapper\DefaultRepositoryManager;
use Goat\Mapper\RepositoryManager;
use Goat\Mapper\Definition\ArrayDefinitionRegistry;
use Goat\Mapper\Definition\DefinitionRegistry;
use Goat\Mapper\Hydration\EntityHydrator\EntityHydratorFactory;
use Goat\Mapper\Hydration\HydratorRegistry\GeneratedHydratorBundleHydratorRegistry;
use Goat\Mapper\Hydration\HydratorRegistry\HydratorRegistry;
use Goat\Mapper\Hydration\Proxy\ProxyFactory;
use Goat\Mapper\Tests\Mock\WithMultipleColumnPrimaryKey;
use Goat\Mapper\Tests\Mock\WithToManyInMappingRelation;
use Goat\Mapper\Tests\Mock\WithToManyInTargetRelation;
use Goat\Mapper\Tests\Mock\WithToOneInMappingRelation;
use Goat\Mapper\Tests\Mock\WithToOneInSourceRelation;
use Goat\Mapper\Tests\Mock\WithToOneInTargetRelation;
use Goat\Mapper\Tests\Mock\WithoutRelation;
use Goat\Query\Query;
use Goat\Runner\Runner;
use Goat\Runner\Testing\NullRunner;
use ProxyManager\Configuration as ProxyManagerConfiguration;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;

trait RepositoryTestTrait
{
    protected static function assertSameSql($expected, $actual, string $message = null): void
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

    private static function normalizeSqlString(string $string): string
    {
        $string = \preg_replace('@\s*(\(|\))\s*@ms', '$1', $string);
        $string = \preg_replace('@\s*,\s*@ms', ',', $string);
        $string = \preg_replace('@\s+@ms', ' ', $string);
        $string = \strtolower($string);
        $string = \trim($string);

        return $string;
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

    private function createEntityHydratorFactory(): EntityHydratorFactory
    {
        return new EntityHydratorFactory(
            $this->createHydratorRegistry(),
            $this->createProxyFactory()
        );
    }

    private function createDefinitionRegistry(): DefinitionRegistry
    {
        return new ArrayDefinitionRegistry([
            WithMultipleColumnPrimaryKey::class => WithMultipleColumnPrimaryKey::toDefinitionArray(),
            WithoutRelation::class => WithoutRelation::toDefinitionArray(),
            WithToManyInMappingRelation::class => WithToManyInMappingRelation::toDefinitionArray(),
            WithToManyInTargetRelation::class => WithToManyInTargetRelation::toDefinitionArray(),
            WithToOneInMappingRelation::class => WithToOneInMappingRelation::toDefinitionArray(),
            WithToOneInSourceRelation::class => WithToOneInSourceRelation::toDefinitionArray(),
            WithToOneInTargetRelation::class => WithToOneInTargetRelation::toDefinitionArray(),
        ]);
    }

    private function createRunner(): Runner
    {
        return new NullRunner();
    }

    private function createRepositoryManager(): RepositoryManager
    {
        return new DefaultRepositoryManager(
            $this->createRunner(),
            $this->createDefinitionRegistry(),
            $this->createEntityHydratorFactory()
        );
    }
}

<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Bridge\Symfony;

use GeneratedHydrator\Bridge\Symfony\GeneratedHydratorBundle;
use Goat\Mapper\DefaultEntityManager;
use Goat\Mapper\EntityManager;
use Goat\Mapper\Bridge\Symfony\DependencyInjection\GoatMapperExtension;
use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

final class KernelConfigurationTest extends TestCase
{
    private function getContainer()
    {
        return new ContainerBuilder(new ParameterBag([
            'kernel.debug'=> false,
            'kernel.bundles' => [GeneratedHydratorBundle::class],
            'kernel.cache_dir' => \sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir' => \dirname(__DIR__),
        ]));
    }

    private function getContainerWithoutGeneratedHydrator()
    {
        return new ContainerBuilder(new ParameterBag([
            'kernel.debug'=> false,
            'kernel.bundles' => [],
            'kernel.cache_dir' => \sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir' => \dirname(__DIR__),
        ]));
    }

    private function getMinimalConfig(): array
    {
        return [];
    }

    public function testGeneratorHydratorBundleIsRequired(): void
    {
        $extension = new GoatMapperExtension();
        $config = $this->getMinimalConfig();

        self::expectExceptionMessageRegExp('/makinacorpus\/generated-hydrator-bundle.*missing/');

        $extension->load([$config], $this->getContainerWithoutGeneratedHydrator());
    }

    public function testExtensionLoad(): void
    {
        $extension = new GoatMapperExtension();
        $config = $this->getMinimalConfig();
        $extension->load([$config], $container = $this->getContainer());

        foreach ([
            'goat_mapper.entity_manager',
        ] as $serviceId) {
            self::assertTrue($container->hasDefinition($serviceId));
        }

        foreach ([
            'goat_mapper.definition_registry',
            DefinitionRegistry::class,
            EntityManager::class,
        ] as $serviceId) {
            self::assertTrue($container->hasAlias($serviceId));
        }

        // self::assertSame(CacheDefinitionRegistry::class, $container->getDefinition('goat_mapper.definition_registry')->getClass());
        self::assertSame(DefaultEntityManager::class, $container->getDefinition('goat_mapper.entity_manager')->getClass());
    }
}

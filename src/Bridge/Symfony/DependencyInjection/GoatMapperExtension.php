<?php

declare(strict_types=1);

namespace Goat\Mapper\Bridge\Symfony\DependencyInjection;

use GeneratedHydrator\Bridge\Symfony\GeneratedHydratorBundle;
use Goat\Mapper\Cache\GeneratorConfiguration;
use Goat\Mapper\Cache\FileLocator\DefaultFileLocator;
use Goat\Mapper\Cache\Inflector\DefaultClassNameInflector;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class GoatMapperExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if (!\in_array(GeneratedHydratorBundle::class, $container->getParameter('kernel.bundles'))) {
            throw new InvalidArgumentException("makinacorpus/generated-hydrator-bundle is missing, did you run 'composer require makinacorpus/generated-hydrator-bundle'?");
        }

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('services.yaml');

        $this->createDefaultPsr4Factory($container, $config);
        $this->configureDefaultHydrator($container, $config);
    }

    private function createDefaultPsr4Factory(ContainerBuilder $container, array $config): void
    {
        $serviceId = 'goat_mapper.cache.generator_configuration';

        $definition = new Definition();
        $definition->setClass(GeneratorConfiguration::class);

        if (isset($config['psr4_namespace_infix']) || isset($config['psr4_namespace_prefix'])) {
            $classNameInflectorId = 'goat_mapper.cache.generator_configuration.class_name_inflector';

            $classNameInflectorDefinition = new Definition();
            $classNameInflectorDefinition->setClass(DefaultClassNameInflector::class);
            $classNameInflectorDefinition->setPrivate(true);
            $classNameInflectorDefinition->setArguments([
                $config['psr4_namespace_prefix'] ?? null,
                $config['psr4_namespace_infix'] ?? DefaultClassNameInflector::DEFAULT_INFIX // @todo allow strict null
            ]);
            $container->setDefinition($classNameInflectorId, $classNameInflectorDefinition);

            $definition->addMethodCall('setClassNameInflector', [new Reference($classNameInflectorId)]);
        }

        if (isset($config['psr4_source_directory'])) {
            $fileLocatorId = 'goat_mapper.cache.generator_configuration.file_locator';

            $fileLocatorDefinition = new Definition();
            $fileLocatorDefinition->setClass(DefaultFileLocator::class);
            $fileLocatorDefinition->setPrivate(true);
            $fileLocatorDefinition->setArguments([
                $config['psr4_source_directory'],
                $config['psr4_namespace_prefix'] ?? null,
            ]);
            $container->setDefinition($fileLocatorId, $fileLocatorDefinition);

            $definition->addMethodCall('setFileLocator', [new Reference($fileLocatorId)]);
            $definition->addMethodCall('setGeneratedClassDirectory', [$config['psr4_source_directory']]);
        }

        $definition->setPrivate(true);
        $container->setDefinition($serviceId, $definition);

        $container->getDefinition('goat_mapper.definition_registry.php')->addMethodCall(
            'setGeneratorConfiguration',
            [new Reference($serviceId)]
        );
    }

    private function configureDefaultHydrator(ContainerBuilder $container, array $config): void
    {
        /*
        $container
            ->getDefinition('generated_hydrator.default')
            ->setArgument(1, [])
            ->setArgument(2, $config['mode'])
        ;
         */
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new GoatMapperConfiguration();
    }
}

<?php

declare(strict_types=1);

namespace Goat\Mapper\Bridge\Symfony\DependencyInjection;

use GeneratedHydrator\Bridge\Symfony\GeneratedHydratorBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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
        /*
        $serviceId = 'generated_hydrator.psr4_configuration';

        $definition = new Definition();
        $definition->setClass(Psr4Factory::class);
        $definition->setPrivate(true);
        $definition->setArguments([
            $config['psr4_source_directory'],
            $config['psr4_namespace_prefix'],
            $config['psr4_namespace_infix'],
        ]);
        $container->setDefinition($serviceId, $definition);

        $container->getDefinition('generated_hydrator.default')->addMethodCall('setPsr4Factory', [new Reference($serviceId)]);
         */
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

<?php

declare(strict_types=1);

namespace Goat\Mapper\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class GoatMapperConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     * @psalm-suppress PossiblyUndefinedMethod
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('goat_mapper');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('psr4_namespace_prefix')
                    ->defaultValue('App')
                ->end()
                ->scalarNode('psr4_namespace_infix')
                    ->defaultValue('Generated\\Mapper\\Definition')
                ->end()
                ->scalarNode('psr4_source_directory')
                    ->defaultValue("%kernel.project_dir%/src")
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}

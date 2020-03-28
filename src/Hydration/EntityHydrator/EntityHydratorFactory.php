<?php

declare(strict_types=1);

namespace Goat\Mapper\Hydration\EntityHydrator;

use Goat\Mapper\Definition\Registry\DefinitionRegistry;
use Goat\Mapper\Hydration\HydratorRegistry\HydratorRegistry;
use Goat\Mapper\Hydration\Proxy\ProxyFactory;

final class EntityHydratorFactory
{
    private DefinitionRegistry $definitionRegistry;
    private HydratorRegistry $hydratorRegistry;
    private ProxyFactory $proxyFactory;

    /** @var array<string,EntityHydrator> */
    private array $cache;

    public function __construct(
        DefinitionRegistry $definitionRegistry,
        HydratorRegistry $hydratorRegistry,
        ?ProxyFactory $proxyFactory = null
    ) {
        $this->definitionRegistry = $definitionRegistry;
        $this->hydratorRegistry = $hydratorRegistry;
        $this->proxyFactory = $proxyFactory ?? new ProxyFactory();
    }

    /**
     * Create hydrator for entity.
     */
    public function createHydrator(string $className): EntityHydrator
    {
        return $this->cache[$className] ?? (
            $this->cache[$className] = new EntityHydrator(
                $this->hydratorRegistry->getHydrator($className),
                $this->definitionRegistry->getDefinition($className),
                $this,
                $this->proxyFactory
            )
        );
    }
}

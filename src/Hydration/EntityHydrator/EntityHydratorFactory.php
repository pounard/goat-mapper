<?php

declare(strict_types=1);

namespace Goat\Mapper\Hydration\EntityHydrator;

use Goat\Mapper\Repository;
use Goat\Mapper\Hydration\HydratorRegistry\HydratorRegistry;
use Goat\Mapper\Hydration\Proxy\ProxyFactory;

class EntityHydratorFactory
{
    /** @var HydratorRegistry */
    private $hydratorRegistry;

    /** @var ProxyFactory */
    private $proxyFactory;

    public function __construct(HydratorRegistry $hydratorRegistry, ?ProxyFactory $proxyFactory = null)
    {
        $this->hydratorRegistry = $hydratorRegistry;
        $this->proxyFactory = $proxyFactory ?? new ProxyFactory();
    }

    /**
     * Create hydrator for entity.
     */
    public function createHydrator(Repository $repository, array $lazyPropertyNames): callable
    {
        $definition = $repository->getRepositoryDefinition();
        $className = $definition->getEntityDefinition()->getClassName();

        $previous = $this->hydratorRegistry->getHydrator($className);

        if (!$lazyPropertyNames) {
            return $previous;
        }

        $lazyProperties = [];
        foreach ($lazyPropertyNames as $propertyName) {
            $relation = $definition->getRelation($propertyName);

            if ($relation->isMultiple()) {
                $lazyProperties[$propertyName] = function ($input) use ($repository, $propertyName) {
                    throw new \Exception("Not implemented yet.");
                };
            } else {
                // For lazy one to one properties, we create a ghost proxy
                // that will lazy load your object upon method access.
                // @todo Handle SQL EXISTS optimisation.
                $lazyProperties[$propertyName] = function ($identifier) use ($repository, $propertyName) {
                    return $this->proxyFactory->getProxy(
                        $repository->getRelatedRepository($propertyName),
                        $identifier
                    );
                };
            }
        }

        return static function (
            array $values
        ) use (
            $lazyProperties,
            $previous
        ) {
            foreach ($lazyProperties as $propertyName => $callable) {
                // @todo ties here the COUNT or EXIST optimisation to skip query
                $identifier = $values[$propertyName] ?? null;

                if (null !== $identifier) {
                    $values[$propertyName] = $callable($values[$propertyName]);
                }
            }

            return $previous($values);
        };
    }
}

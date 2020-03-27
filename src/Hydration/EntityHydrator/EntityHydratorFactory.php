<?php

declare(strict_types=1);

namespace Goat\Mapper\Hydration\EntityHydrator;

use Goat\Mapper\Definition\DefinitionRegistry;
use Goat\Mapper\Definition\Identifier;
use Goat\Mapper\Hydration\HydratorRegistry\HydratorRegistry;
use Goat\Mapper\Hydration\Proxy\ProxyFactory;

/**
 * @todo Inject relation fetcher for unit testing
 */
class EntityHydratorFactory
{
    /** @var DefinitionRegistry */
    private $definitionRegistry;

    /** @var HydratorRegistry */
    private $hydratorRegistry;

    /** @var ProxyFactory */
    private $proxyFactory;

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
    public function createHydrator(EntityHydratorContext $context): callable
    {
        $definition = $this
            ->definitionRegistry
            ->getDefinition(
                $context->className
            )
        ;

        $previous = $this
            ->hydratorRegistry
            ->getHydrator(
                $context->className
            )
        ;

        if (!$context->lazyPropertyNames) {
            return static function (Identifier $identifier, $values) use ($previous) {
                return $previous($values);
            };
        }

        $lazyProperties = [];

        foreach ($context->lazyPropertyNames as $propertyName) {
            $relation = $definition->getRelation($propertyName);

            if ($relation->isMultiple()) {
                // @todo Handle SQL EXISTS optimisation.
                // For collections, we can safely create the lazy collections
                // directly and return it. The SQL query will not be run until
                // the collection is accessed.
                $lazyProperties[$propertyName] = static function (Identifier $id) use ($context, $propertyName) {
                    return $context->relationFetcher->collection($context->className, $propertyName, $id);
                };
            } else {
                // For lazy one to one properties, we create a ghost proxy
                // that will lazy load your object upon method access.
                // @todo Handle SQL EXISTS optimisation.
                // @todo Use a ghost proxy instead?
                // @todo We do create a ghost whereas the result could return null, this is WRONG.
                $lazyProperties[$propertyName] = function (Identifier $id) use ($context, $relation, $propertyName) {
                    return $this->proxyFactory->getProxy(
                        $relation->getClassName(),
                        static function () use ($context, $propertyName, $id) {
                            return $context->relationFetcher->single($context->className, $propertyName, $id);
                        }
                    );
                };
            }
        }

        return static function (Identifier $identifier, array $values) use ($lazyProperties, $previous) {
            foreach ($lazyProperties as $propertyName => $callable) {
                /*
                 * @todo handle this properly: in case we a single identifier
                 *   or a key with multiple columns, but they are in the source
                 *   table, we should be able to get directly this key from the
                 *   result row and do a direct load that wouldn't require an
                 *   extra JOIN as the fetch would do otherwise. 
                 *
                if (isset($values[$propertyName])) {
                    $values[$propertyName] = $callable($identifier);
                } else {
                    $values[$propertyName] = $callable($identifier);
                }
                 */
                $values[$propertyName] = $callable($identifier);
            }

            return $previous($values);
        };
    }
}

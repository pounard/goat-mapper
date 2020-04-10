<?php

declare(strict_types=1);

namespace Goat\Mapper\Hydration\EntityHydrator;

use Goat\Mapper\Definition\PrimaryKey;
use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Definition\Graph\Relation;
use Goat\Mapper\Hydration\Proxy\ProxyFactory;
use Goat\Mapper\Query\Relation\RelationFetcher;

final class EntityHydrator
{
    private $previous;
    private EntityHydratorFactory $entityHydratorFactory;
    private ?PrimaryKey $primaryKey;
    private ProxyFactory $proxyFactory;
    private Entity $definition;

    public function __construct(
        callable $previous,
        Entity $definition,
        EntityHydratorFactory $entityHydratorFactory,
        ProxyFactory $proxyFactory
    ) {
        $this->definition = $definition;
        $this->entityHydratorFactory = $entityHydratorFactory;
        $this->previous = $previous;
        $this->primaryKey = $definition->getPrimaryKey();
        $this->proxyFactory = $proxyFactory;
    }

    private function hydrateRelations(array $values, RelationFetcher $fetcher): array
    {
        $className = $this->definition->getClassName();
        $id = $this->primaryKey->createIdentifierFromRow($values);

        foreach ($this->definition->getRelations() as $relation) {
            \assert($relation instanceof Relation);

            $propertyName = $relation->getName();

            if (\array_key_exists($propertyName, $values)) {
                // It was eagerly loaded.
                $value = $values[$propertyName];

                if (!\is_array($value)) {
                    // It may have been hydrated, one way or another. This
                    // should not happen, really, this is supposed to be goat
                    // query result raw data, but we never now.
                    // @todo instrument here
                    continue;
                }

                $values[$propertyName] = $this
                    ->entityHydratorFactory
                    ->createHydrator(
                        $relation->getEntity()->getClassName()
                    )
                    ->hydrate(
                        $value,
                        $fetcher
                    )
                ;
                continue;
            }

            if ($relation->isMultiple()) {
                // Easy one, fetcher will always return lazy collections.
                // We don't care about proxifying it further.
                $values[$propertyName] = $fetcher->collection($className, $propertyName, $id);
                continue;
            }

            // For lazy one to one properties, we create a ghost proxy
            // that will lazy load your object upon method access.
            // @todo For now, don't use this, always do eager whenever possible
            // @todo Handle SQL EXISTS optimisation.
            // @todo Use a ghost proxy instead?
            // @todo We do create a ghost whereas the result could return null, this is WRONG.
            //   https://github.com/Ocramius/ProxyManager/blob/master/docs/lazy-loading-ghost-object.md
            $values[$propertyName] = $this->proxyFactory->getProxy(
                $relation->getClassName(),
                static function () use ($fetcher, $className, $propertyName, $id) {
                    return $fetcher->single($className, $propertyName, $id);
                }
            );
        }

        return $values;
    }

    public function hydrate(array $values, RelationFetcher $fetcher)
    {
        return \call_user_func($this->previous, $this->hydrateRelations($values, $fetcher));
    }
}

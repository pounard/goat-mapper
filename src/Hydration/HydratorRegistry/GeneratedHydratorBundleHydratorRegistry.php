<?php

declare(strict_types=1);

namespace Goat\Mapper\Hydration\HydratorRegistry;

use GeneratedHydrator\Bridge\Symfony\Hydrator;

class GeneratedHydratorBundleHydratorRegistry implements HydratorRegistry
{
    private Hydrator $hydrator;

    public function __construct(Hydrator $hydrator)
    {
        $this->hydrator = $hydrator;
    }

    /**
     * {@inheritdoc}
     */
    public function getHydrator(string $className): callable
    {
        return function (array $values) use ($className) {
            return $this->hydrator->createAndHydrate($className, $values);
        };
    }
}

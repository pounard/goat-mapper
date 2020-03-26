<?php

declare(strict_types=1);

namespace Goat\Mapper\Hydration\Collection;

/**
 * Empty collection.
 */
class EmptyCollection extends AbstractCollection
{
    /**
     * Load data, and return an array.
     */
    protected function doInitialize(): iterable
    {
        return [];
    }

    /**
     * Allow implementors to return a count without initializing.
     */
    protected function doCount(): ?int
    {
        return 0;
    }
}

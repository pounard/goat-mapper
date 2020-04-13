<?php

declare(strict_types=1);

namespace Goat\Mapper\Hydration\EntityHydrator;

use Goat\Mapper\Query\Relation\RelationFetcher;

interface EntityHydrator
{
    public function hydrate(array $values, RelationFetcher $fetcher);
}

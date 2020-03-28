<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Relation;

use Goat\Mapper\Definition\Identifier;
use Goat\Mapper\Hydration\Collection\Collection;
use Goat\Mapper\Hydration\Collection\EmptyCollection;

final class EmptyResultSet implements ResultSet
{
    /**
     * {@inheritdoc}
     */
    public function get(Identifier $id): Collection
    {
        return new EmptyCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function first(Identifier $id): ?object
    {
        return null;
    }
}

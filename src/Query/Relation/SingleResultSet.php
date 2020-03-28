<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Relation;

use Goat\Mapper\Definition\Identifier;
use Goat\Mapper\Hydration\Collection\Collection;
use Goat\Mapper\Hydration\Collection\DefaultCollection;

/**
 * Use this when you have a single object to fetch and you don't care about
 * identifier lookups. Common use case is for proxy objects for lazy loaded
 * any to one relations.
 */
final class SingleResultSet implements ResultSet
{
    /** @var null|object */
    private $loadedEntity;

    public function __construct(?object $loadedEntity)
    {
        $this->loadedEntity = $loadedEntity;
    }

    /**
     * {@inheritdoc}
     */
    public function get(Identifier $id): Collection
    {
        return new DefaultCollection([$this->loadedEntity]);
    }

    /**
     * {@inheritdoc}
     */
    public function first(Identifier $id): ?object
    {
        return $this->loadedEntity;
    }
}

<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Relation;

use Goat\Mapper\Definition\Identifier;
use Goat\Mapper\Hydration\Collection\Collection;

/**
 * Result set is for eager loading of resultats that need an additional
 * SQL query.
 *
 * Consider you loaded N entities of type T, which have a any to many
 * relationship toward a U entity. You may aggregate all T identifiers
 * and do a single SQL query to fetch all related U entities.
 *
 * Those preloaded entities will be mapped into this result set, then
 * dispatched during hydration, or upon access using a proxy implementation.
 *
 * @var ResultSet<T,U>
 */
interface ResultSet
{
    /**
     * Fetch a any to many relationship collection result for a single entity.
     *
     * @param Identifier<T> $id
     *
     * @return Collection<U>
     */
    public function get(Identifier $id): Collection;

    /**
     * Fetch a any to one relationship result for a single entity.
     *
     * @param Identifier<T> $id
     *
     * @return null|<U>
     */
    public function first(Identifier $id): ?object;
}

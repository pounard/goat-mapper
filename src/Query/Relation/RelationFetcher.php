<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Relation;

use Goat\Mapper\Definition\Identifier;
use Goat\Mapper\Hydration\Collection\Collection;

/**
 * This object is responsible for either:
 *
 *  - earger loading of any to many relationships, for loading related target
 *    entities for multiple source entities at once,
 *
 *  - lazy loading any to any relationships, for loading one or more related
 *    target entities for a single source entity.
 *
 * You may find additional documentation on that topic on the ResultSet class.
 *
 * @see ResultSet
 *   For additional documentation.
 */
interface RelationFetcher
{
    /**
     * Fetch a single related entity.
     *
     * @param string $className
     * @param string $propertyName
     * @param Identifier<T> $id
     *
     * @return <T>
     *   Where U is the target relation entity class.
     */
    public function single(string $className, string $propertyName, Identifier $id): ?object;

    /**
     * Creates a lazy collection of related entities.
     *
     * @param string $className
     * @param string $propertyName
     * @param Identifier<T> $id
     *
     * @return Collection<U>
     *   Where U is the target relation entity class.
     */
    public function collection(string $className, string $propertyName, Identifier $id): Collection;

    /**
     * Fetch a single value for each input
     *
     * @param string $className
     * @param string $propertyName
     * @param Identifier<T>[] $identifiers
     *
     * @return ResultSet<T,U>
     *   Where T is the source entity type, U the target relation
     */
    public function bulk(string $className, string $propertyName, array $identifiers): ResultSet;
}

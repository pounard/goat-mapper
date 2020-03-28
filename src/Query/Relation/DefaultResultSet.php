<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Relation;

use Goat\Mapper\Definition\Identifier;
use Goat\Mapper\Error\QueryError;
use Goat\Mapper\Hydration\Collection\Collection;
use Goat\Mapper\Hydration\Collection\DefaultCollection;

class DefaultResultSet implements ResultSet
{
    /** @var array<string,Collection> */
    private $results = [];

    /** @param callable|iterable|Collection $result */
    public function add(Identifier $id, $result)
    {
        if ($result instanceof Collection) {
            $this->results[$id->getHash()] = $result;
        } else {
            $this->results[$id->getHash()] = new DefaultCollection($result);
        }
    }

    private function noResult(): void
    {
        throw new QueryError("No result exists for this identifier");
    }

    /**
     * Fetch a any to many relationship collection result for a single entity.
     *
     * @param Identifier<T> $id
     *
     * @return Collection<U>
     */
    public function get(Identifier $id): Collection
    {
        return $this->results[$id->getHash()] ?? $this->noResult();
    }

    /**
     * Fetch a any to one relationship result for a single entity.
     *
     * @param Identifier<T> $id
     *
     * @return null|<U>
     */
    public function first(Identifier $id): ?object
    {
        $result = $this->results[$id->getHash()] ?? null;

        if ($result) {
            foreach ($result as $value) {
                return $value;
            }
        }

        return null;
    }
}

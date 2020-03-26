<?php

declare(strict_types=1);

namespace Goat\Mapper\Hydration\Collection;

/**
 * This object allows to return an iterator and a count at the same time
 * without the need of iterating over the iterator to count.
 *
 * Ideal for SQL results.
 *
 * @var CollectionInitializerResult<T>
 */
final class CollectionInitializerResult
{
    /** @var iterable<T> */
    private $values;

    /** @var ?int */
    private $count = null;

    /**
     * @param iterable<T> $values
     */
    public function __construct(iterable $values, ?int $count = null)
    {
        $this->values = $values;
        $this->count = $count;
    }

    /**
     * @return iterable<T>
     */
    public function getValues(): iterable
    {
        return $this->values;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }
}

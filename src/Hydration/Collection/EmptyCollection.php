<?php

declare(strict_types=1);

namespace Goat\Mapper\Hydration\Collection;

/**
 * Empty collection.
 *
 * @codeCoverageIgnore
 */
class EmptyCollection implements Collection, \Iterator
{
    public function count(): int
    {
        return 0;
    }

    /**
     * @param mixed $offset
     */
    public function offsetExists($offset): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        throw new \BadMethodCallException("Collections are read-only.");
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        throw new \BadMethodCallException("Collections are read-only.");
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
    }
}

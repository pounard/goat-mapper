<?php

declare(strict_types=1);

namespace Goat\Mapper\Hydration\Collection;

/**
 * Object collections will be as opened as possible.
 *
 * \ArrayAccess interface is here for convenance, nevertheless it would be
 * best for performances not to use it, further you cannot predict what wil
 * the keys look like.
 *
 * @var Collection<T>
 *
 * @todo Implement a rewindable iterator.
 */
abstract class AbstractCollection implements Collection, \IteratorAggregate
{
    /** @var iterable<T> */
    private $values;

    /** @var null|int */
    private $count;

    /**
     * @param iterable<T> $values
     */
    public function __construct(?array $values = null, ?int $count = null)
    {
        $this->values = $values;
        $this->count = $count;
    }

    /**
     * Set collection count.
     */
    protected function setCount(int $count): void
    {
        $this->count = $count;
    }

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
        return null;
    }

    /**
     * Internal initialization.
     */
    private function initialize(): void
    {
        $this->values = $this->doInitialize();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): iterable
    {
        if (null === $this->values) {
            $this->initialize();
        }

        foreach ($this->values as $key => $value) {
            yield $key => $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if (null === $this->values) {
            $this->initialize();
        }
        if (!\is_array($this->values)) {
            throw new \BadMethodCallException("This collection cannot be accessed by offset");
        }
        return $this->values[$offset] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        if (null === $this->values) {
            $this->initialize();
        }
        if (!\is_array($this->values)) {
            throw new \BadMethodCallException("This collection cannot be accessed by offset");
        }
        return \array_key_exists($offset, $this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException("Collections are read-only.");
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException("Collections are read-only.");
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        if (null !== $this->count) {
            return $this->count;
        }
        if (null !== ($count = $this->doCount())) {
            return $this->count = $count;
        }
        if (null === $this->values) {
            $this->initialize();
            if (null !== $this->count) {
                // initialize() call might have populated count.
                return $this->count;
            }
        }

        if (\is_countable($this->values)) {
            return $this->count = \count($this->values);
        }

        throw new \BadMethodCallException("This collection is non-countable.");
    }
}

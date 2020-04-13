<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition;

/**
 * List of identifiers.
 */
class IdentifierList implements \IteratorAggregate
{
    protected array $data = [];

    public function __construct(iterable $identifiers)
    {
        foreach ($identifiers as $id) {
            \assert($id instanceof Identifier);
            $this->data[$id->getHash()] = $id;
        }
    }

    final public function isEmpty(): bool
    {
        return empty($this->data);
    }

    final public function exists(Identifier $id): bool
    {
        if (!$this->data) {
            return false;
        }

        return isset($this->data[$id->getHash()]);
    }

    final public function toArray(): array
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    final public function getIterator()
    {
        foreach ($this->data as $id) {
            yield $id;
        }
    }
}

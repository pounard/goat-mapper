<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition;

class Identifier
{
    /** @var mixed[] */
    private $values;

    /**
     * @param Column[] $columns
     */
    public function __construct(array $values)
    {
        $this->values = \array_keys($values);
    }

    public function toArray(): array
    {
        return $this->values;
    }

    public function isCompatible(Key $key): bool
    {
        // @todo Check values types?
        return $key->count() === \count($this->values);
    }
}

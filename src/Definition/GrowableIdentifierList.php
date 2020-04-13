<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition;

use Goat\Mapper\Error\QueryError;

class GrowableIdentifierList extends IdentifierList
{
    private bool $locked = false;

    public function __construct(?iterable $identifiers = null)
    {
        parent::__construct($identifiers ?? []);
    }

    public function add(Identifier $id): void
    {
        if ($this->locked) {
            throw new QueryError("Identifier list is locked.");
        }

        $this->data[$id->getHash()] = $id;
    }

    public function lock(): void
    {
        $this->locked = true;
    }
}

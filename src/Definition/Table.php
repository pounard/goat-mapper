<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition;

class Table
{
    /** @var string */
    private $name;

    /** @var null|string */
    private $schema;

    public function __construct(string $name, ?string $schema = null)
    {
        $this->name = $name;
        $this->schema = $schema;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSchema(): ?string
    {
        return $this->schema;
    }

    public function toString(): string
    {
        if ($this->schema) {
            return $this->schema.'.'.$this->name;
        }
        return $this->name;
    }
}

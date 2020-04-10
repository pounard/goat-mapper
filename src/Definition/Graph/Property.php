<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph;

/**
 * Represents a single entity property.
 */
abstract class Property implements Node
{
    use WithOwner;

    private string $name;
    private bool $allowsNull = false;

    public function __construct(string $name, bool $allowsNull = false)
    {
        $this->name = $name;
        $this->allowsNull = $allowsNull;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function allowsNull(): bool
    {
        return $this->allowsNull;
    }
}

<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph\Impl;

use Goat\Mapper\Definition\Graph\Entity;
use Goat\Mapper\Error\ConfigurationError;

abstract class AbstractProperty extends AbstractNode
{
    private string $name;
    private bool $allowsNull = true;
    private ?Entity $owner = null;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setAllowsNull(bool $allowsNull): void
    {
        $this->allowsNull = $allowsNull;
    }

    public function allowsNull(): bool
    {
        return $this->allowsNull;
    }

    public function setOwner(Entity $owner): void
    {
        if ($this->owner) {
            throw new ConfigurationError("You cannot initialize owner twice.");
        }
        $this->owner = $owner;
    }

    public function getOwner(): Entity
    {
        if (!$this->owner) {
            throw new ConfigurationError("Unitialized owner.");
        }
        return $this->owner;
    }
}

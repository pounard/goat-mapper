<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph;

use Goat\Driver\ConfigurationError;

trait WithOwner
{
    private ?Entity $owner = null;

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

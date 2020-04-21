<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph\Impl;

use Goat\Mapper\Definition\Graph\Node;
use Goat\Mapper\Error\ConfigurationError;
use Goat\Mapper\Error\IncompleteObjectInitializationError;

abstract class AbstractNode implements Node
{
    private ?string $internalId = null;

    /**
     * Set internal identifier.
     */
    public function setInternalId(string $internalId): void
    {
        if ($this->internalId) {
            throw new ConfigurationError("You cannot initialize internal identifier twice.");
        }
        $this->internalId = $internalId;
    }

    /**
     * {@inheritdoc}
     */
    public function getInternalId(): string
    {
        if (!$this->internalId) {
            throw new IncompleteObjectInitializationError("Unitialized internal identifier.");
        }
        return $this->internalId;
    }
}

<?php

declare(strict_types=1);

namespace Goat\Mapper\Definition\Graph;

interface Node
{
    /**
     * Get internal predictable identifier.
     */
    public function getInternalId(): string;

    /** @return Node[] */
    public function getChildren(): iterable;
}

<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Graph;

abstract class Visitor
{
    /**
     * React on root node traversal.
     */
    public function onRootNode(RootNode $node, EntityQuery $context): void
    {
    }

    /**
     * React on property node traversal.
     */
    public function onPropertyNode(PropertyNode $node, Node $parent, EntityQuery $context): void
    {
    }
}

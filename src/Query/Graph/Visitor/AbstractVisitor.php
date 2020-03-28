<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Graph\Visitor;

use Goat\Mapper\Query\Graph\EntityQuery;
use Goat\Mapper\Query\Graph\Node;
use Goat\Mapper\Query\Graph\PropertyNode;
use Goat\Mapper\Query\Graph\RootNode;

abstract class AbstractVisitor implements RootVisitor, PropertyVisitor
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

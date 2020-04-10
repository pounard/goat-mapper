<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Graph\Visitor;

use Goat\Mapper\Query\Entity\EntityQuery;
use Goat\Mapper\Query\Graph\Node;
use Goat\Mapper\Query\Graph\PropertyNode;

interface PropertyVisitor extends Visitor
{
    /**
     * React on property node traversal.
     */
    public function onPropertyNode(PropertyNode $node, Node $parent, EntityQuery $context): void;
}

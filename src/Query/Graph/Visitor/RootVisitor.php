<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Graph\Visitor;

use Goat\Mapper\Query\Graph\EntityQuery;
use Goat\Mapper\Query\Graph\RootNode;

interface RootVisitor extends Visitor
{
    /**
     * React on root node traversal.
     */
    public function onRootNode(RootNode $node, EntityQuery $context): void;
}

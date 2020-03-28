<?php

declare(strict_types=1);

namespace Goat\Mapper\Query\Graph;

final class Traverser
{
    /** @var iterable<Visitor> */
    private iterable $visitors = [];

    public function __construct()
    {
        $this->visitors[] = new SelectColumnVisitor();
        $this->visitors[] = new EagerJoinVisitor();
        $this->visitors[] = new MatchVisitor();
    }

    private function doTraverse(PropertyNode $node, Node $parent, EntityQuery $query): void
    {
        foreach ($this->visitors as $visitor) {
            \assert($visitor instanceof Visitor);

            $visitor->onPropertyNode($node, $parent, $query);
        }

        foreach ($node->getChildren() as $child) {
            $this->doTraverse($child, $node, $query);
        }
    }

    public function traverse(EntityQuery $query): void
    {
        $rootNode = $query->getRootNode();

        foreach ($this->visitors as $visitor) {
            \assert($visitor instanceof Visitor);

            $visitor->onRootNode($rootNode, $query);
        }

        foreach ($rootNode->getChildren() as $child) {
            $this->doTraverse($child, $rootNode, $query);
        }
    }
}
